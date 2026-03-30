<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

function gv_response($success, $message = '', $extra = []) {
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'message' => (string)$message,
    ], $extra));
    exit;
}

function gv_get_api_key() {
    $candidates = [
        getenv('GOOGLE_VISION_API_KEY') ?: '',
        $_ENV['GOOGLE_VISION_API_KEY'] ?? '',
        $_SERVER['GOOGLE_VISION_API_KEY'] ?? '',
        defined('GOOGLE_VISION_API_KEY') ? GOOGLE_VISION_API_KEY : '',
    ];

    foreach ($candidates as $value) {
        $trimmed = trim((string)$value);
        if ($trimmed !== '') {
            return $trimmed;
        }
    }

    return '';
}

function gv_extract_average_confidence($visionPayload) {
    $responses = $visionPayload['responses'] ?? [];
    if (!is_array($responses) || count($responses) === 0) {
        return 0;
    }

    $fullText = $responses[0]['fullTextAnnotation'] ?? null;
    if (!is_array($fullText)) {
        return 0;
    }

    $pages = $fullText['pages'] ?? [];
    if (!is_array($pages)) {
        return 0;
    }

    $sum = 0.0;
    $count = 0;

    foreach ($pages as $page) {
        $blocks = $page['blocks'] ?? [];
        if (!is_array($blocks)) continue;

        foreach ($blocks as $block) {
            $paragraphs = $block['paragraphs'] ?? [];
            if (!is_array($paragraphs)) continue;

            foreach ($paragraphs as $paragraph) {
                $words = $paragraph['words'] ?? [];
                if (!is_array($words)) continue;

                foreach ($words as $word) {
                    if (isset($word['confidence']) && is_numeric($word['confidence'])) {
                        $sum += (float)$word['confidence'];
                        $count++;
                    }
                }
            }
        }
    }

    if ($count <= 0) {
        return 0;
    }

    return round(($sum / $count) * 100, 2);
}

function gv_call_vision_api($apiKey, $payload) {
    $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . rawurlencode($apiKey);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $responseBody = curl_exec($ch);
    $curlErrNo = curl_errno($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErrNo !== 0) {
        gv_response(false, 'Google Vision request failed: ' . $curlErr);
    }

    $visionData = json_decode((string)$responseBody, true);
    if (!is_array($visionData)) {
        gv_response(false, 'Invalid response from Google Vision API');
    }

    if ($httpCode >= 400) {
        $apiMessage = $visionData['error']['message'] ?? ('Google Vision API error (HTTP ' . $httpCode . ')');
        gv_response(false, $apiMessage);
    }

    $first = $visionData['responses'][0] ?? [];
    if (isset($first['error']['message']) && $first['error']['message'] !== '') {
        gv_response(false, (string)$first['error']['message']);
    }

    return $visionData;
}

if (!isset($_SESSION['user_id'])) {
    gv_response(false, 'Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gv_response(false, 'Invalid request method');
}

$action = $_POST['action'] ?? '';
if (!in_array($action, ['scan_id', 'health_check'], true)) {
    gv_response(false, 'Invalid action');
}

if (!function_exists('curl_init')) {
    gv_response(false, 'cURL extension is required for Google Vision OCR');
}

$apiKey = gv_get_api_key();
if ($apiKey === '') {
    gv_response(false, 'Google Vision API key is not configured on the server');
}

if ($action === 'health_check') {
    $tinyPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO0x7mQAAAAASUVORK5CYII=';
    $payload = [
        'requests' => [[
            'image' => [
                'content' => $tinyPngBase64,
            ],
            'features' => [[
                'type' => 'TEXT_DETECTION',
                'maxResults' => 1,
            ]],
            'imageContext' => [
                'languageHints' => ['en'],
            ],
        ]],
    ];

    gv_call_vision_api($apiKey, $payload);
    gv_response(true, 'Google Vision is configured and reachable', [
        'configured' => true,
    ]);
}

if (!isset($_FILES['id_image']) || !is_array($_FILES['id_image'])) {
    gv_response(false, 'No ID image uploaded');
}

$file = $_FILES['id_image'];
$errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($errorCode !== UPLOAD_ERR_OK) {
    gv_response(false, 'Failed to upload ID image for scanning');
}

$tmpFile = (string)($file['tmp_name'] ?? '');
if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
    gv_response(false, 'Invalid uploaded file');
}

$maxBytes = 8 * 1024 * 1024;
$fileSize = (int)($file['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > $maxBytes) {
    gv_response(false, 'ID image must be between 1 byte and 8MB');
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
    gv_response(false, 'Unsupported ID image format. Please upload JPG or PNG.');
}

$binary = @file_get_contents($tmpFile);
if ($binary === false || $binary === '') {
    gv_response(false, 'Unable to read uploaded image');
}

$payload = [
    'requests' => [[
        'image' => [
            'content' => base64_encode($binary),
        ],
        'features' => [[
            'type' => 'DOCUMENT_TEXT_DETECTION',
            'maxResults' => 1,
        ]],
        'imageContext' => [
            'languageHints' => ['en', 'fil'],
        ],
    ]],
];

$visionData = gv_call_vision_api($apiKey, $payload);

$first = $visionData['responses'][0] ?? [];

$text = '';
if (!empty($first['fullTextAnnotation']['text'])) {
    $text = (string)$first['fullTextAnnotation']['text'];
} elseif (!empty($first['textAnnotations'][0]['description'])) {
    $text = (string)$first['textAnnotations'][0]['description'];
}

$confidence = gv_extract_average_confidence($visionData);

gv_response(true, 'Scan complete', [
    'text' => $text,
    'confidence' => $confidence,
]);
