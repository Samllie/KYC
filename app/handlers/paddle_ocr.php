<?php
ini_set('display_errors', '0');
ini_set('html_errors', '0');

register_shutdown_function(static function () {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int)$error['type'], $fatalTypes, true)) {
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }

    echo json_encode([
        'success' => false,
        'message' => 'OCR handler fatal error: ' . (string)($error['message'] ?? 'Unknown error'),
    ]);
});

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

function po_response($success, $message = '', $extra = []) {
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'message' => (string)$message,
    ], $extra));
    exit;
}

set_error_handler(static function ($severity, $message) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    po_response(false, 'OCR handler error: ' . (string)$message);
});

function po_get_ocr_url() {
    $value = trim((string)(getenv('PYTHON_OCR_URL') ?: ''));
    if ($value !== '') return $value;

    if (!empty($_ENV['PYTHON_OCR_URL'])) return trim((string)$_ENV['PYTHON_OCR_URL']);
    if (!empty($_SERVER['PYTHON_OCR_URL'])) return trim((string)$_SERVER['PYTHON_OCR_URL']);

    return 'http://127.0.0.1:5001/ocr';
}

function po_get_health_url() {
    $value = trim((string)(getenv('PYTHON_OCR_HEALTH_URL') ?: ''));
    if ($value !== '') return $value;

    if (!empty($_ENV['PYTHON_OCR_HEALTH_URL'])) return trim((string)$_ENV['PYTHON_OCR_HEALTH_URL']);
    if (!empty($_SERVER['PYTHON_OCR_HEALTH_URL'])) return trim((string)$_SERVER['PYTHON_OCR_HEALTH_URL']);

    $ocrUrl = po_get_ocr_url();
    if (str_ends_with($ocrUrl, '/ocr')) {
        return substr($ocrUrl, 0, -4) . '/health';
    }

    return rtrim($ocrUrl, '/') . '/health';
}

function po_normalize_date($value) {
    $raw = trim((string)$value);
    if ($raw === '') return '';

    if (preg_match('/\b(\d{4})[-\/](\d{2})[-\/](\d{2})\b/', $raw, $matches)) {
        return sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]);
    }

    if (!preg_match('/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/', $raw, $matches)) {
        return '';
    }

    $a = (int)$matches[1];
    $b = (int)$matches[2];
    $year = $matches[3];

    $month = $a;
    $day = $b;

    if ($a > 12 && $b <= 12) {
        $month = $b;
        $day = $a;
    }

    return sprintf('%s-%02d-%02d', $year, $month, $day);
}

function po_pick_id_number($lines, $idType) {
    $type = strtolower((string)$idType);
    $cleanLines = array_values(array_filter(array_map(static function ($line) {
        return trim((string)$line);
    }, (array)$lines), static function ($line) {
        return $line !== '';
    }));

    $labelPattern = '/\b(license\s*no\.?|id\s*no\.?|id\s*number|passport\s*no\.?|sss\s*no\.?|tin\s*no\.?|prc\s*no\.?|gsis\s*no\.?|philhealth\s*no\.?|pag-?ibig\s*(mid|no\.?)?|voter\'?s?\s*(id|no\.?)?)\b/i';
    $blacklistPattern = '/^(license|number|no|id|agency|code|expiration|date|signature|sex|gender|address)$/i';
    $candidatePattern = '/\b[A-Z0-9][A-Z0-9\-\/]{5,24}\b/i';

    $typePatterns = [];
    if (str_contains($type, 'passport')) {
        $typePatterns[] = '/\b[A-Z0-9]{8,10}\b/i';
    } elseif (str_contains($type, 'driver')) {
        $typePatterns[] = '/\b[A-Z]\d{2}-\d{2}-\d{6}\b/i';
        $typePatterns[] = '/\b[A-Z]{1,3}-?\d{2,3}-?\d{4,7}\b/i';
    } elseif (str_contains($type, 'umid') || str_contains($type, 'philsys')) {
        $typePatterns[] = '/\b\d{4}-?\d{4}-?\d{4}-?\d{4}\b/';
    } elseif (str_contains($type, 'sss') || str_contains($type, 'gsis')) {
        $typePatterns[] = '/\b\d{2}-?\d{7}-?\d\b/';
    } elseif (str_contains($type, 'prc')) {
        $typePatterns[] = '/\b\d{7}\b/';
    } elseif (str_contains($type, 'tin')) {
        $typePatterns[] = '/\b\d{3}-?\d{3}-?\d{3}(?:-?\d{3})?\b/';
    } elseif (str_contains($type, 'philhealth')) {
        $typePatterns[] = '/\b\d{2}-?\d{9}-?\d\b/';
    } elseif (str_contains($type, 'pagibig')) {
        $typePatterns[] = '/\b\d{4}-?\d{4}-?\d{4}\b/';
    } elseif (str_contains($type, 'postal')) {
        $typePatterns[] = '/\b[A-Z]{1,3}-?\d{6,12}\b/i';
        $typePatterns[] = '/\b\d{4}-?\d{4}-?\d{4}\b/';
    } elseif (str_contains($type, 'voter')) {
        $typePatterns[] = '/\b[A-Z0-9]{8,20}\b/i';
    } elseif (str_contains($type, 'senior') || str_contains($type, 'ofw')) {
        $typePatterns[] = '/\b[A-Z]{1,4}-?\d{4,14}\b/i';
        $typePatterns[] = '/\b\d{6,16}\b/';
    }
    $typePatterns[] = $candidatePattern;

    $normalizePhilsysNumber = static function ($value) {
        $digits = preg_replace('/\D+/', '', (string)$value);
        if (strlen($digits) === 16) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4, 4) . '-' . substr($digits, 8, 4) . '-' . substr($digits, 12, 4);
        }
        return trim((string)$value);
    };

    if (str_contains($type, 'philsys')) {
        $philsysPattern = '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/';
        $philsysLabel = '/\b(philsys\s*(card\s*)?number|philsys\s*no\.?|pcn|psn|national\s*id\s*no\.?)\b/i';

        foreach ($cleanLines as $index => $line) {
            if (!preg_match($philsysLabel, $line)) {
                continue;
            }

            for ($offset = 0; $offset <= 6; $offset++) {
                $targetLine = (string)($cleanLines[$index + $offset] ?? '');
                if ($targetLine === '') {
                    continue;
                }

                if ($offset === 0) {
                    $targetLine = preg_replace($philsysLabel, ' ', $targetLine);
                }

                if (preg_match($philsysPattern, $targetLine, $match)) {
                    return $normalizePhilsysNumber($match[0]);
                }
            }
        }
    }

    if (str_contains($type, 'driver')) {
        $driverPatterns = [
            '/\b[A-Z]\d{2}-\d{2}-\d{6}\b/i',
            '/\b[A-Z]{1,3}-?\d{2,3}-?\d{4,7}\b/i',
        ];

        foreach ($cleanLines as $index => $line) {
            if (!preg_match('/\blicense\s*no\.?\b/i', $line)) {
                continue;
            }

            for ($offset = 0; $offset <= 6; $offset++) {
                $targetLine = $cleanLines[$index + $offset] ?? '';
                if ($targetLine === '') {
                    continue;
                }

                if ($offset === 0) {
                    $targetLine = preg_replace('/\blicense\s*no\.?\b/i', ' ', $targetLine);
                }

                foreach ($driverPatterns as $pattern) {
                    if (preg_match($pattern, $targetLine, $match)) {
                        $value = trim((string)($match[0] ?? ''));
                        if ($value !== '' && !preg_match($blacklistPattern, $value)) {
                            return $value;
                        }
                    }
                }
            }
        }
    }

    $extractCandidate = static function ($line) use ($typePatterns, $blacklistPattern, $type, $normalizePhilsysNumber) {
        $line = trim((string)$line);
        if ($line === '') return '';

        foreach ($typePatterns as $pattern) {
            if (preg_match($pattern, $line, $match)) {
                $value = trim((string)$match[0]);
                if ($value !== '' && !preg_match($blacklistPattern, $value)) {
                    if (str_contains($type, 'philsys')) {
                        return $normalizePhilsysNumber($value);
                    }
                    return $value;
                }
            }
        }

        return '';
    };

    foreach ($cleanLines as $index => $line) {
        if (!preg_match($labelPattern, $line)) {
            continue;
        }

        $sameLine = preg_replace($labelPattern, ' ', $line);
        $candidate = $extractCandidate($sameLine);
        if ($candidate !== '') {
            return $candidate;
        }

        for ($offset = 1; $offset <= 2; $offset++) {
            $nextIndex = $index + $offset;
            if (!isset($cleanLines[$nextIndex])) {
                break;
            }

            $candidate = $extractCandidate($cleanLines[$nextIndex]);
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    foreach ($cleanLines as $line) {
        $candidate = $extractCandidate($line);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function po_pick_name($lines, $idType = '') {
    $type = strtolower((string)$idType);
    $cleanLines = array_values(array_filter(array_map(static function ($line) {
        return trim((string)$line);
    }, (array)$lines), static function ($line) {
        return $line !== '';
    }));

    $isNoiseLine = static function ($line) {
        if (preg_match('/\b(republic|department|transportation|land transportation office|driver\'s?\s*license|national id|philsys|pambansang|signature|assistant secretary|agency code|expiration|blood type|eyes color|conditions|dl codes)\b/i', $line)) {
            return true;
        }
        if (preg_match('/\b(id|number|birth|date|sex|gender|address|nationality|issue|valid|license no)\b/i', $line)) {
            return true;
        }
        return false;
    };

    $normalizeName = static function ($value) {
        $value = preg_replace('/[^A-Za-z\s,.-]/', ' ', (string)$value);
        $value = preg_replace('/\s+/', ' ', (string)$value);
        return trim((string)$value, " ,.-");
    };

    $extractAfterLabel = static function ($source, $labelPattern) use ($normalizeName, $isNoiseLine) {
        foreach ($source as $index => $line) {
            if (!preg_match($labelPattern, $line)) {
                continue;
            }

            $sameLine = preg_replace($labelPattern, ' ', (string)$line);
            $sameLine = preg_replace('/[:\-]+/', ' ', (string)$sameLine);
            $candidate = $normalizeName($sameLine);
            if ($candidate !== '' && !$isNoiseLine($candidate) && !preg_match('/\d/', $candidate)) {
                return $candidate;
            }

            for ($offset = 1; $offset <= 2; $offset++) {
                $nextLine = (string)($source[$index + $offset] ?? '');
                if ($nextLine === '') {
                    continue;
                }
                $candidate = $normalizeName($nextLine);
                if ($candidate !== '' && !$isNoiseLine($candidate) && !preg_match('/\d/', $candidate)) {
                    return $candidate;
                }
            }
        }

        return '';
    };

    if (str_contains($type, 'philsys')) {
        $surname = $extractAfterLabel($cleanLines, '/\b(surname|last\s*name)\b/i');
        $given = $extractAfterLabel($cleanLines, '/\b(given\s*name|first\s*name)\b/i');
        $middle = $extractAfterLabel($cleanLines, '/\b(middle\s*name)\b/i');

        if ($surname !== '' || $given !== '') {
            $full = '';
            if ($surname !== '') {
                $full = $surname;
            }
            if ($given !== '') {
                $full = $full !== '' ? ($full . ', ' . $given) : $given;
            }
            if ($middle !== '') {
                $full = trim($full . ' ' . $middle);
            }

            if ($full !== '') {
                return preg_replace('/\s+/', ' ', $full);
            }
        }
    }

    foreach ($cleanLines as $index => $line) {
        if (preg_match('/\b(last\s*name|first\s*name|middle\s*name|name)\b/i', $line)) {
            if (preg_match('/\bname\b\s*[:\-]\s*([A-Za-z][A-Za-z\s,.-]{4,})/i', $line, $match)) {
                $candidate = $normalizeName($match[1]);
                if ($candidate !== '' && !$isNoiseLine($candidate)) {
                    return $candidate;
                }
            }

            for ($offset = 1; $offset <= 2; $offset++) {
                $nextIndex = $index + $offset;
                if (!isset($cleanLines[$nextIndex])) break;
                $candidate = $normalizeName($cleanLines[$nextIndex]);
                if ($candidate === '' || $isNoiseLine($candidate) || preg_match('/\d/', $candidate)) {
                    continue;
                }
                if (str_word_count(str_replace(',', ' ', $candidate)) >= 2) {
                    return $candidate;
                }
            }
        }
    }

    foreach ($cleanLines as $line) {
        $candidate = $normalizeName($line);
        if ($candidate === '' || $isNoiseLine($candidate) || preg_match('/\d/', $candidate)) {
            continue;
        }
        if (str_contains($candidate, ',') || str_word_count(str_replace(',', ' ', $candidate)) >= 3) {
            return $candidate;
        }
    }

    return '';
}

function po_pick_birthdate($lines) {
    $cleanLines = array_values(array_filter(array_map(static function ($line) {
        return trim((string)$line);
    }, (array)$lines), static function ($line) {
        return $line !== '';
    }));

    $datePattern = '/\b(\d{4}[-\/]\d{2}[-\/]\d{2}|\d{1,2}[-\/]\d{1,2}[-\/]\d{4})\b/';

    foreach ($cleanLines as $index => $line) {
        if (!preg_match('/\b(date\s*of\s*birth|birth\s*date|birth|dob)\b/i', $line)) {
            continue;
        }

        if (preg_match($datePattern, $line, $match)) {
            $normalized = po_normalize_date($match[1]);
            if ($normalized !== '') return $normalized;
        }

        $next = $cleanLines[$index + 1] ?? '';
        if ($next !== '' && preg_match($datePattern, $next, $match)) {
            $normalized = po_normalize_date($match[1]);
            if ($normalized !== '') return $normalized;
        }
    }

    $fallback = [];
    foreach ($cleanLines as $line) {
        if (preg_match($datePattern, $line, $match)) {
            $normalized = po_normalize_date($match[1]);
            if ($normalized !== '') {
                $ts = strtotime($normalized);
                if ($ts !== false) {
                    $fallback[] = ['value' => $normalized, 'time' => $ts];
                }
            }
        }
    }

    if (!$fallback) return '';

    usort($fallback, static function ($a, $b) {
        return $a['time'] <=> $b['time'];
    });

    $now = time();
    foreach ($fallback as $item) {
        $years = (int)floor(($now - $item['time']) / (365.25 * 86400));
        if ($years >= 10 && $years <= 120) {
            return $item['value'];
        }
    }

    return $fallback[0]['value'] ?? '';
}

function po_pick_gender($lines, $idType = '') {
    $type = strtolower((string)$idType);
    $cleanLines = array_values(array_filter(array_map(static function ($line) {
        return trim((string)$line);
    }, (array)$lines), static function ($line) {
        return $line !== '';
    }));

    if (str_contains($type, 'driver') || str_contains($type, 'philsys')) {
        foreach ($cleanLines as $index => $line) {
            if (!preg_match('/\bsex(?:\s+at\s+birth)?\b/i', $line)) {
                continue;
            }

            for ($offset = 0; $offset <= 6; $offset++) {
                $targetLine = strtoupper((string)($cleanLines[$index + $offset] ?? ''));
                if ($targetLine === '') {
                    continue;
                }

                if ($offset === 0) {
                    $targetLine = preg_replace('/\bSEX(?:\s+AT\s+BIRTH)?\b/i', ' ', $targetLine);
                }

                if (preg_match('/\bFEMALE\b/', $targetLine) || preg_match('/^\s*F\s*$/', $targetLine)) return 'female';
                if (preg_match('/\bMALE\b/', $targetLine) || preg_match('/^\s*M\s*$/', $targetLine)) return 'male';
            }
        }
    }

    $joined = strtolower(implode("\n", $lines));
    if (preg_match('/\bfemale\b/', $joined)) return 'female';
    if (preg_match('/\bmale\b/', $joined)) return 'male';

    foreach ($cleanLines as $index => $line) {
        if (!preg_match('/\b(sex|gender)\b/i', (string)$line)) {
            continue;
        }

        $same = strtoupper((string)$line);
        if (preg_match('/\bF\b/', $same)) return 'female';
        if (preg_match('/\bM\b/', $same)) return 'male';

        $next = isset($lines[$index + 1]) ? strtoupper((string)$lines[$index + 1]) : '';
        if (preg_match('/\bF\b/', $next)) return 'female';
        if (preg_match('/\bM\b/', $next)) return 'male';
    }

    return '';
}

function po_pick_nationality($lines) {
    $joined = strtolower(implode("\n", (array)$lines));
    if (preg_match('/\b(filipino|philippine|philippines)\b/', $joined)) return 'Philippine';
    if (preg_match('/\bphl\b/', $joined)) return 'Philippine';
    return '';
}

function po_pick_address($lines) {
    $cleanLines = array_values(array_filter(array_map(static function ($line) {
        return trim((string)$line);
    }, (array)$lines), static function ($line) {
        return $line !== '';
    }));

    $stopPattern = '/\b(license\s*no\.?|id\s*no\.?|id\s*number|expiration|agency\s*code|signature|blood\s*type|eyes\s*color|dl\s*codes|conditions|date\s*of\s*birth)\b/i';

    foreach ($cleanLines as $index => $line) {
        if (!preg_match('/\b(address|residence|home\s*address|present\s*address|permanent\s*address)\b/i', $line)) {
            continue;
        }

        $value = preg_replace('/^.*?(address|residence|home\s*address|present\s*address|permanent\s*address)\s*[:\-]?\s*/i', '', $line);
        $parts = [];
        if (trim((string)$value) !== '') {
            $parts[] = trim((string)$value, " ,");
        }

        for ($offset = 1; $offset <= 3; $offset++) {
            $nextIndex = $index + $offset;
            if (!isset($cleanLines[$nextIndex])) break;
            $nextLine = trim((string)$cleanLines[$nextIndex]);
            if ($nextLine === '') continue;
            if (preg_match($stopPattern, $nextLine)) break;
            $parts[] = trim($nextLine, " ,");
        }

        $address = trim(implode(', ', array_filter($parts)), " ,");
        if ($address !== '') {
            return $address;
        }
    }

    return '';
}

if (!isset($_SESSION['user_id'])) {
    po_response(false, 'Unauthorized access');
}

if (!function_exists('curl_init')) {
    po_response(false, 'cURL extension is required for OCR integration');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!in_array($action, ['scan_id', 'health_check'], true)) {
    po_response(false, 'Invalid action');
}

if ($action === 'health_check') {
    $healthUrl = po_get_health_url();

    $ch = curl_init($healthUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);

    $responseBody = curl_exec($ch);
    $curlErrNo = curl_errno($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErrNo !== 0) {
        po_response(false, 'OCR service is unreachable: ' . $curlErr);
    }

    $payload = json_decode((string)$responseBody, true);
    if ($httpCode >= 400 || !is_array($payload) || empty($payload['success'])) {
        $message = is_array($payload) ? ($payload['message'] ?? 'OCR service health check failed') : 'OCR service health check failed';
        po_response(false, (string)$message);
    }

    po_response(true, 'OCR service is healthy and reachable', [
        'configured' => true,
        'service' => 'PaddleOCR',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    po_response(false, 'Invalid request method');
}

if (!isset($_FILES['id_image']) || !is_array($_FILES['id_image'])) {
    po_response(false, 'No ID image uploaded');
}

$file = $_FILES['id_image'];
$errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorCode !== UPLOAD_ERR_OK) {
    po_response(false, 'Failed to upload ID image for scanning');
}

$tmpFile = (string)($file['tmp_name'] ?? '');
if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
    po_response(false, 'Invalid uploaded image');
}

$maxBytes = 5 * 1024 * 1024;
$fileSize = (int)($file['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > $maxBytes) {
    po_response(false, 'ID image must be between 1 byte and 5MB');
}

$mime = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $mime = (string)finfo_file($finfo, $tmpFile);
        finfo_close($finfo);
    }
}

$allowedMime = ['image/jpeg', 'image/png'];
if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
    po_response(false, 'Unsupported ID image format. Please upload JPG or PNG.');
}

$idType = trim((string)($_POST['idType'] ?? ''));
$ocrUrl = po_get_ocr_url();

$postData = [
    'image' => new CURLFile($tmpFile, $mime !== '' ? $mime : 'image/png', basename((string)($file['name'] ?? 'id.png'))),
    'id_type' => $idType,
];

$ch = curl_init($ocrUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 45,
]);

$responseBody = curl_exec($ch);
$curlErrNo = curl_errno($ch);
$curlErr = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErrNo !== 0) {
    po_response(false, 'OCR service request failed: ' . $curlErr);
}

$payload = json_decode((string)$responseBody, true);
if (!is_array($payload)) {
    po_response(false, 'Invalid OCR response from Python service');
}

if ($httpCode >= 400 || empty($payload['success'])) {
    po_response(false, (string)($payload['message'] ?? 'OCR processing failed'));
}

$textLines = [];
if (isset($payload['text']) && is_array($payload['text'])) {
    foreach ($payload['text'] as $line) {
        $lineText = trim((string)$line);
        if ($lineText !== '') {
            $textLines[] = $lineText;
        }
    }
}

$confidenceItems = [];
if (isset($payload['confidence']) && is_array($payload['confidence'])) {
    foreach ($payload['confidence'] as $c) {
        if (is_numeric($c)) {
            $confidenceItems[] = (float)$c;
        }
    }
}

$avgConfidence = 0;
if (count($confidenceItems) > 0) {
    $avgConfidence = round((array_sum($confidenceItems) / count($confidenceItems)) * 100, 2);
}

$parsed = [
    'idNumber' => po_pick_id_number($textLines, $idType),
    'fullName' => po_pick_name($textLines, $idType),
    'birthdate' => po_pick_birthdate($textLines),
    'gender' => po_pick_gender($textLines, $idType),
    'nationality' => po_pick_nationality($textLines),
    'address' => po_pick_address($textLines),
];

po_response(true, 'OCR completed', [
    'service' => 'PaddleOCR',
    'text' => implode("\n", $textLines),
    'textLines' => $textLines,
    'confidence' => $avgConfidence,
    'confidenceItems' => $confidenceItems,
    'parsed' => $parsed,
]);
