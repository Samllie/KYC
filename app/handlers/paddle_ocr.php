<?php
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
    $joined = strtoupper(implode("\n", $lines));
    $type = strtolower((string)$idType);
    $patterns = [];

    if (str_contains($type, 'passport')) {
        $patterns[] = '/\b[A-Z0-9]{8,10}\b/';
    } elseif (str_contains($type, 'driver')) {
        $patterns[] = '/\b[A-Z]{1,3}-?\d{2,3}-?\d{4,7}\b/';
    } elseif (str_contains($type, 'umid') || str_contains($type, 'philsys')) {
        $patterns[] = '/\b\d{4}-?\d{4}-?\d{4}-?\d{4}\b/';
    } elseif (str_contains($type, 'sss') || str_contains($type, 'gsis')) {
        $patterns[] = '/\b\d{2}-?\d{7}-?\d\b/';
    } elseif (str_contains($type, 'prc')) {
        $patterns[] = '/\b\d{7}\b/';
    } elseif (str_contains($type, 'tin')) {
        $patterns[] = '/\b\d{3}-?\d{3}-?\d{3}(?:-?\d{3})?\b/';
    } else {
        $patterns[] = '/\b[A-Z0-9][A-Z0-9\-\/]{5,24}\b/';
    }

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $joined, $match)) {
            return trim($match[0]);
        }
    }

    foreach ($lines as $line) {
        if (preg_match('/\b(id|number|no\.?|licen[cs]e|passport)\b/i', $line)) {
            if (preg_match('/\b[A-Z0-9][A-Z0-9\-\/]{5,24}\b/i', $line, $match)) {
                return trim($match[0]);
            }
        }
    }

    return '';
}

function po_pick_name($lines) {
    foreach ($lines as $line) {
        $normalized = trim((string)$line);
        if ($normalized === '') continue;
        if (preg_match('/\b(id|number|birth|date|sex|gender|address|nationality|signature|issue|valid)\b/i', $normalized)) continue;
        if (preg_match('/\d/', $normalized)) continue;

        $lettersOnly = preg_replace('/[^A-Za-z\s,.-]/', '', $normalized);
        $lettersOnly = preg_replace('/\s+/', ' ', $lettersOnly);
        $lettersOnly = trim($lettersOnly);

        if (strlen($lettersOnly) >= 5) {
            return $lettersOnly;
        }
    }

    return '';
}

function po_pick_birthdate($lines) {
    foreach ($lines as $line) {
        if (preg_match('/\b(\d{4}[-\/]\d{2}[-\/]\d{2}|\d{1,2}[-\/]\d{1,2}[-\/]\d{4})\b/', $line, $match)) {
            $normalized = po_normalize_date($match[1]);
            if ($normalized !== '') {
                return $normalized;
            }
        }
    }

    return '';
}

function po_pick_gender($lines) {
    $joined = strtolower(implode("\n", $lines));
    if (preg_match('/\bmale\b/', $joined)) return 'male';
    if (preg_match('/\bfemale\b/', $joined)) return 'female';
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
    'fullName' => po_pick_name($textLines),
    'birthdate' => po_pick_birthdate($textLines),
    'gender' => po_pick_gender($textLines),
    'nationality' => '',
    'address' => '',
];

po_response(true, 'OCR completed', [
    'service' => 'PaddleOCR',
    'text' => implode("\n", $textLines),
    'textLines' => $textLines,
    'confidence' => $avgConfidence,
    'confidenceItems' => $confidenceItems,
    'parsed' => $parsed,
]);
