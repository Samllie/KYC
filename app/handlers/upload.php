<?php
/**
 * Temporary upload endpoint.
 * Stores files under uploads/tmp/user_{id}/ and returns temp paths.
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once __DIR__ . '/upload_utils.php';
session_start();

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'upload_temp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $filesField = $_FILES['documents'] ?? null;
    if ($filesField === null) {
        $response['message'] = 'No files provided';
        echo json_encode($response);
        exit;
    }

    $files = kyc_normalize_files_array($filesField);
    $result = kyc_handle_temp_uploads($_SESSION['user_id'], $files);

    if (!($result['success'] ?? false)) {
        $response['message'] = $result['message'] ?? 'Upload failed';
        echo json_encode($response);
        exit;
    }

    $response['success'] = true;
    $response['files'] = $result['files'] ?? [];
    echo json_encode($response);
    exit;
}

if ($action === 'delete_temp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = trim($_POST['path'] ?? '');
    if ($path === '') {
        $response['message'] = 'Path is required';
        echo json_encode($response);
        exit;
    }

    $result = kyc_delete_temp_upload($_SESSION['user_id'], $path);
    if (!($result['success'] ?? false)) {
        $response['message'] = $result['message'] ?? 'Delete failed';
        echo json_encode($response);
        exit;
    }

    $response['success'] = true;
    $response['message'] = 'Deleted';
    echo json_encode($response);
    exit;
}

$response['message'] = 'Invalid action';
echo json_encode($response);
