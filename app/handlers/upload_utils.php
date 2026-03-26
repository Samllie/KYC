<?php
/**
 * Upload utilities for saving temporary uploads and finalizing them.
 */

function kyc_get_project_root() {
    // upload_utils.php is in app/handlers
    return realpath(__DIR__ . '/../../');
}

function kyc_get_uploads_root() {
    $root = kyc_get_project_root();
    if ($root === false) {
        return null;
    }
    return $root . DIRECTORY_SEPARATOR . 'uploads';
}

function kyc_ensure_dir($dirPath) {
    if (is_dir($dirPath)) return true;
    return mkdir($dirPath, 0775, true);
}

function kyc_sanitize_filename($name) {
    $name = basename($name);
    $name = preg_replace('/\s+/', '_', $name);
    $name = preg_replace('/[^A-Za-z0-9._-]/', '', $name);
    $name = preg_replace('/_+/', '_', $name);
    if ($name === '' || $name === null) {
        return 'file';
    }
    return substr($name, 0, 180);
}

function kyc_get_extension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $ext;
}

function kyc_allowed_extensions() {
    return ['pdf', 'jpg', 'jpeg', 'png'];
}

function kyc_allowed_mime_types() {
    return [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];
}

function kyc_normalize_files_array($fileField) {
    // Supports both single and multi uploads
    if (!isset($fileField['name'])) return [];

    if (is_array($fileField['name'])) {
        $out = [];
        $count = count($fileField['name']);
        for ($i = 0; $i < $count; $i++) {
            $out[] = [
                'name' => $fileField['name'][$i],
                'type' => $fileField['type'][$i] ?? '',
                'tmp_name' => $fileField['tmp_name'][$i] ?? '',
                'error' => $fileField['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $fileField['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    return [[
        'name' => $fileField['name'],
        'type' => $fileField['type'] ?? '',
        'tmp_name' => $fileField['tmp_name'] ?? '',
        'error' => $fileField['error'] ?? UPLOAD_ERR_NO_FILE,
        'size' => $fileField['size'] ?? 0,
    ]];
}

function kyc_get_user_tmp_dir($userId) {
    $uploadsRoot = kyc_get_uploads_root();
    if ($uploadsRoot === null) return null;
    return $uploadsRoot . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'user_' . intval($userId);
}

function kyc_is_path_under($candidate, $root) {
    $candidateReal = realpath($candidate);
    $rootReal = realpath($root);
    if ($candidateReal === false || $rootReal === false) return false;

    $candidateReal = rtrim($candidateReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $rootReal = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    return strncmp($candidateReal, $rootReal, strlen($rootReal)) === 0;
}

function kyc_abs_from_rel_uploads($relPath) {
    $uploadsRoot = kyc_get_uploads_root();
    if ($uploadsRoot === null) return null;

    $relPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
    $relPath = ltrim($relPath, DIRECTORY_SEPARATOR);

    return $uploadsRoot . DIRECTORY_SEPARATOR . $relPath;
}

function kyc_handle_temp_uploads($userId, $files, $maxBytesPerFile = 5242880) {
    $uploadsRoot = kyc_get_uploads_root();
    if ($uploadsRoot === null) {
        return ['success' => false, 'message' => 'Uploads folder not found'];
    }

    if (!kyc_ensure_dir($uploadsRoot)) {
        return ['success' => false, 'message' => 'Unable to create uploads folder'];
    }

    $tmpDir = kyc_get_user_tmp_dir($userId);
    if ($tmpDir === null || !kyc_ensure_dir($tmpDir)) {
        return ['success' => false, 'message' => 'Unable to create temp upload folder'];
    }

    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

    $saved = [];
    foreach ($files as $f) {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed for one of the files'];
        }

        $originalName = $f['name'] ?? 'file';
        $safeOriginal = kyc_sanitize_filename($originalName);
        $ext = kyc_get_extension($safeOriginal);
        if (!in_array($ext, kyc_allowed_extensions(), true)) {
            return ['success' => false, 'message' => 'Unsupported file type: ' . $ext];
        }

        $size = intval($f['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytesPerFile) {
            return ['success' => false, 'message' => 'File too large (max 5MB each)'];
        }

        $tmpName = $f['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'message' => 'Invalid upload source'];
        }

        $mime = $finfo ? finfo_file($finfo, $tmpName) : ($f['type'] ?? '');
        if ($mime && !in_array($mime, kyc_allowed_mime_types(), true)) {
            return ['success' => false, 'message' => 'Unsupported MIME type'];
        }

        $rand = bin2hex(random_bytes(8));
        $finalName = 'tmp_' . intval($userId) . '_' . time() . '_' . $rand . '.' . $ext;
        $destAbs = $tmpDir . DIRECTORY_SEPARATOR . $finalName;

        if (!move_uploaded_file($tmpName, $destAbs)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file'];
        }

        $rel = 'tmp/user_' . intval($userId) . '/' . $finalName;
        $saved[] = [
            'temp_path' => 'uploads/' . $rel,
            'original_name' => $safeOriginal,
            'file_size' => $size,
            'file_type' => $mime,
        ];
    }

    if ($finfo) finfo_close($finfo);

    return ['success' => true, 'files' => $saved];
}

function kyc_delete_temp_upload($userId, $tempRelOrAbs) {
    $uploadsRoot = kyc_get_uploads_root();
    if ($uploadsRoot === null) {
        return ['success' => false, 'message' => 'Uploads folder not found'];
    }

    // Accept only relative paths beginning with uploads/tmp/user_{id}/
    $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $tempRelOrAbs);
    $path = ltrim($path, DIRECTORY_SEPARATOR);

    if (stripos($path, 'uploads' . DIRECTORY_SEPARATOR) === 0) {
        $path = substr($path, strlen('uploads' . DIRECTORY_SEPARATOR));
    }

    $expectedPrefix = 'tmp' . DIRECTORY_SEPARATOR . 'user_' . intval($userId) . DIRECTORY_SEPARATOR;
    if (stripos($path, $expectedPrefix) !== 0) {
        return ['success' => false, 'message' => 'Invalid temp path'];
    }

    $abs = $uploadsRoot . DIRECTORY_SEPARATOR . $path;

    $userTmpDir = kyc_get_user_tmp_dir($userId);
    if ($userTmpDir === null || !kyc_is_path_under($abs, $userTmpDir)) {
        return ['success' => false, 'message' => 'Invalid temp path'];
    }

    if (!file_exists($abs)) {
        return ['success' => true];
    }

    if (!unlink($abs)) {
        return ['success' => false, 'message' => 'Unable to delete file'];
    }

    return ['success' => true];
}

function kyc_finalize_temp_uploads($userId, $uploadedFiles, $clientId, $kycId) {
    $uploadsRoot = kyc_get_uploads_root();
    if ($uploadsRoot === null) {
        return ['success' => false, 'message' => 'Uploads folder not found'];
    }

    if (!kyc_ensure_dir($uploadsRoot)) {
        return ['success' => false, 'message' => 'Unable to create uploads folder'];
    }

    $finalized = [];

    foreach ($uploadedFiles as $entry) {
        $tempPath = '';
        $original = '';
        $size = null;
        $mime = null;

        if (is_string($entry)) {
            $tempPath = $entry;
        } elseif (is_array($entry)) {
            // If the entry already has a finalized `file_path`, keep it as-is.
            // This allows resuming drafts where attachments already exist in `documents`
            // (not only those stored temporarily under `uploads/tmp/user_{id}`).
            if (!empty($entry['file_path'])) {
                $finalized[] = [
                    'file_name' => $entry['file_name'] ?? ($entry['original_name'] ?? basename((string)$entry['file_path'])),
                    'file_type' => $entry['file_type'] ?? null,
                    'file_size' => isset($entry['file_size']) ? intval($entry['file_size']) : null,
                    'file_path' => $entry['file_path'],
                ];
                continue;
            }
            $tempPath = $entry['temp_path'] ?? $entry['tempPath'] ?? '';
            $original = $entry['original_name'] ?? $entry['originalName'] ?? '';
            $size = isset($entry['file_size']) ? intval($entry['file_size']) : null;
            $mime = $entry['file_type'] ?? null;
        }

        if ($tempPath === '') continue;

        // Strip leading uploads/
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $tempPath);
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        if (stripos($path, 'uploads' . DIRECTORY_SEPARATOR) === 0) {
            $path = substr($path, strlen('uploads' . DIRECTORY_SEPARATOR));
        }

        $expectedPrefix = 'tmp' . DIRECTORY_SEPARATOR . 'user_' . intval($userId) . DIRECTORY_SEPARATOR;
        if (stripos($path, $expectedPrefix) !== 0) {
            continue;
        }

        $tempAbs = $uploadsRoot . DIRECTORY_SEPARATOR . $path;
        $userTmpDir = kyc_get_user_tmp_dir($userId);
        if ($userTmpDir === null || !file_exists($tempAbs) || !kyc_is_path_under($tempAbs, $userTmpDir)) {
            continue;
        }

        $ext = kyc_get_extension($original !== '' ? $original : basename($tempAbs));
        if (!in_array($ext, kyc_allowed_extensions(), true)) {
            $ext = kyc_get_extension(basename($tempAbs));
        }

        $rand = bin2hex(random_bytes(8));
        $finalName = 'client_' . intval($clientId) . '_kyc_' . intval($kycId) . '_' . time() . '_' . $rand . ($ext ? ('.' . $ext) : '');
        $finalAbs = $uploadsRoot . DIRECTORY_SEPARATOR . $finalName;

        $moved = @rename($tempAbs, $finalAbs);
        if (!$moved) {
            $moved = @copy($tempAbs, $finalAbs);
            if ($moved) {
                @unlink($tempAbs);
            }
        }

        if (!$moved) {
            continue;
        }

        $finalRel = 'uploads/' . $finalName;
        if ($original === '') {
            $original = basename($finalName);
        }
        if ($size === null) {
            $size = @filesize($finalAbs);
        }

        $finalized[] = [
            'file_name' => $original,
            'file_type' => $mime,
            'file_size' => $size,
            'file_path' => $finalRel,
        ];
    }

    return ['success' => true, 'files' => $finalized];
}
