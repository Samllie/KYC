<?php
/**
 * ID OCR endpoint.
 *
 * Accepts a temp upload path under uploads/tmp/user_{id}/,
 * runs OCR (Tesseract), and returns extracted form fields.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/upload_utils.php';
session_start();

function id_ocr_response($success, $message = '', $extra = []) {
	echo json_encode(array_merge([
		'success' => (bool)$success,
		'message' => (string)$message,
	], $extra));
	exit;
}

if (!isset($_SESSION['user_id'])) {
	id_ocr_response(false, 'Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	id_ocr_response(false, 'Invalid request method');
}

$action = $_POST['action'] ?? '';
if ($action !== 'extract_fields') {
	id_ocr_response(false, 'Invalid action');
}

$tempPathRaw = trim((string)($_POST['temp_path'] ?? ''));
if ($tempPathRaw === '') {
	id_ocr_response(false, 'Missing temp upload path');
}

$idTypeRaw = strtolower(trim((string)($_POST['id_type'] ?? '')));
$allowedIdTypes = ['philsys', 'passport', 'drivers_license', 'umid', 'prc', 'postal', 'voters', 'sss', 'gsis', 'other'];
$idType = in_array($idTypeRaw, $allowedIdTypes, true) ? $idTypeRaw : 'other';

$userId = intval($_SESSION['user_id']);
$uploadsRoot = kyc_get_uploads_root();
$userTmpDir = kyc_get_user_tmp_dir($userId);

if ($uploadsRoot === null || $userTmpDir === null) {
	id_ocr_response(false, 'Uploads folder is not configured');
}

$normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $tempPathRaw);
$normalized = ltrim($normalized, DIRECTORY_SEPARATOR);
if (stripos($normalized, 'uploads' . DIRECTORY_SEPARATOR) === 0) {
	$normalized = substr($normalized, strlen('uploads' . DIRECTORY_SEPARATOR));
}

$expectedPrefix = 'tmp' . DIRECTORY_SEPARATOR . 'user_' . $userId . DIRECTORY_SEPARATOR;
if (stripos($normalized, $expectedPrefix) !== 0) {
	id_ocr_response(false, 'Invalid temp upload path');
}

$absPath = $uploadsRoot . DIRECTORY_SEPARATOR . $normalized;
if (!file_exists($absPath) || !kyc_is_path_under($absPath, $userTmpDir)) {
	id_ocr_response(false, 'Temporary file not found');
}

$ext = strtolower((string)pathinfo($absPath, PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
	id_ocr_response(false, 'Unsupported file type for OCR');
}

function id_ocr_resolve_tesseract_cmd() {
	$envCmd = trim((string)getenv('KYC_TESSERACT_CMD'));
	if ($envCmd !== '') {
		if (is_file($envCmd)) return $envCmd;
		return $envCmd; // Keep as command name if user passed a PATH-resolvable alias.
	}

	$defaultCandidates = [
		'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
		'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
		'C:\\Tesseract-OCR\\tesseract.exe',
		realpath(__DIR__ . '/../../tools/Tesseract-OCR/tesseract.exe') ?: '',
		realpath(__DIR__ . '/../../tools/tesseract/tesseract.exe') ?: '',
	];

	foreach ($defaultCandidates as $candidate) {
		if (is_file($candidate)) {
			return $candidate;
		}
	}

	return 'tesseract';
}

function id_ocr_command_exists($command) {
	if (is_file($command)) {
		return true;
	}

	$probeCommand = 'where ' . escapeshellarg($command) . ' 2>NUL';
	$probeOutput = [];
	$probeExit = 1;
	@exec($probeCommand, $probeOutput, $probeExit);
	return $probeExit === 0 && !empty($probeOutput);
}

$tesseractCmd = id_ocr_resolve_tesseract_cmd();
if (!id_ocr_command_exists($tesseractCmd)) {
	id_ocr_response(false, 'Tesseract is not installed or not accessible. Install Tesseract OCR and set KYC_TESSERACT_CMD if needed.');
}

$ocrInputPath = $absPath;
if ($ext === 'pdf') {
	id_ocr_response(false, 'PDF OCR is not enabled. Please upload a JPG or PNG ID image.');
}

$ocrLang = trim((string)getenv('KYC_OCR_LANG'));
if ($ocrLang === '') {
	$ocrLang = 'eng';
}

$cmd = escapeshellarg($tesseractCmd)
	. ' ' . escapeshellarg($ocrInputPath)
	. ' stdout --psm 6 -l ' . escapeshellarg($ocrLang)
	. ' 2>&1';

$ocrOutput = [];
$ocrExit = 1;
@exec($cmd, $ocrOutput, $ocrExit);
$ocrText = trim(implode("\n", is_array($ocrOutput) ? $ocrOutput : []));

if ($ocrExit !== 0) {
	$snippet = trim(substr($ocrText, 0, 220));
	$msg = 'Tesseract OCR command failed.';
	if ($snippet !== '') {
		$msg .= ' ' . $snippet;
	}
	id_ocr_response(false, $msg);
}

if ($ocrText === '') {
	id_ocr_response(false, 'No OCR text extracted. The image may be unreadable or too low quality.');
}

function id_ocr_normalize_date($value) {
	$value = trim((string)$value);
	if ($value === '') return null;

	$ts = strtotime($value);
	if ($ts === false) return null;
	return date('Y-m-d', $ts);
}

function id_ocr_clean_name($value) {
	$value = strtoupper(trim((string)$value));
	$value = preg_replace('/[^A-Z\s\-\']/u', '', $value);
	$value = preg_replace('/\s+/', ' ', (string)$value);
	return trim((string)$value);
}

function id_ocr_match_line_value($text, $patterns) {
	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $text, $m)) {
			return trim((string)($m[1] ?? ''));
		}
	}
	return '';
}

function id_ocr_text_lines($text) {
	$raw = preg_split('/\R+/', strtoupper((string)$text));
	if (!is_array($raw)) return [];

	$lines = [];
	foreach ($raw as $line) {
		$line = preg_replace('/\s+/', ' ', trim((string)$line));
		if ($line !== '') {
			$lines[] = $line;
		}
	}

	return $lines;
}

function id_ocr_label_normalize($line) {
	$line = strtoupper((string)$line);
	$line = preg_replace('/[^A-Z]/', '', $line);
	return (string)$line;
}

function id_ocr_looks_like_label_line($line) {
	$n = id_ocr_label_normalize($line);
	if ($n === '') return false;
	return preg_match('/(SURNAME|LASTNAME|GIVENNAME|FIRSTNAME|MIDDLENAME|DATEOFBIRTH|BIRTHDATE|DOB|APELYIDO|PANGALAN|KAPANGANAKAN|SEX|NATIONALITY)/', $n) === 1;
}

function id_ocr_extract_by_labels($lines, $labelTokens) {
	if (!is_array($lines) || empty($lines)) return '';
	if (!is_array($labelTokens) || empty($labelTokens)) return '';

	for ($i = 0; $i < count($lines); $i++) {
		$line = (string)$lines[$i];
		$lineNorm = id_ocr_label_normalize($line);
		if ($lineNorm === '') continue;

		$matched = false;
		foreach ($labelTokens as $token) {
			if ($token !== '' && strpos($lineNorm, $token) !== false) {
				$matched = true;
				break;
			}
		}
		if (!$matched) continue;

		if (preg_match('/[:\-]\s*(.+)$/', $line, $m)) {
			$value = trim((string)$m[1]);
			if ($value !== '' && !id_ocr_looks_like_label_line($value)) {
				return $value;
			}
		}

		if (preg_match('/\b(?:SURNAME|LAST\s*NAME|GIVEN\s*NAME(?:S)?|FIRST\s*NAME|MIDDLE\s*NAME|MI|APELYIDO|PANGALAN|KAPANGANAKAN|DOB|DATE\s*OF\s*BIRTH|BIRTH\s*DATE)\b\s*(.+)$/', $line, $m)) {
			$value = trim((string)$m[1]);
			$value = ltrim($value, ': -');
			if ($value !== '' && !id_ocr_looks_like_label_line($value)) {
				return $value;
			}
		}

		$next = isset($lines[$i + 1]) ? trim((string)$lines[$i + 1]) : '';
		if ($next !== '' && !id_ocr_looks_like_label_line($next)) {
			return $next;
		}
	}

	return '';
}

function id_ocr_extract_birthdate_fallback($text) {
	$text = strtoupper((string)$text);
	$monthPattern = '(?:JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:T(?:EMBER)?)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)';
	$patterns = [
		'/\b((?:19|20)\d{2}[\/\-](?:0?[1-9]|1[0-2])[\/\-](?:0?[1-9]|[12][0-9]|3[01]))\b/',
		'/\b((?:0?[1-9]|1[0-2])[\/\-](?:0?[1-9]|[12][0-9]|3[01])[\/\-](?:19|20)\d{2})\b/',
		'/\b((?:0?[1-9]|[12][0-9]|3[01])\s+' . $monthPattern . '\s+(?:19|20)\d{2})\b/',
		'/\b(' . $monthPattern . '\s+(?:0?[1-9]|[12][0-9]|3[01]),?\s+(?:19|20)\d{2})\b/',
	];

	$candidates = [];
	foreach ($patterns as $p) {
		if (preg_match_all($p, $text, $m) && !empty($m[1])) {
			foreach ($m[1] as $raw) {
				$normalized = id_ocr_normalize_date($raw);
				if ($normalized !== null) {
					$candidates[] = $normalized;
				}
			}
		}
	}

	if (empty($candidates)) return null;

	$todayTs = strtotime(date('Y-m-d'));
	$picked = null;
	$pickedTs = null;
	foreach ($candidates as $dateStr) {
		$ts = strtotime($dateStr);
		if ($ts === false) continue;
		if ($ts > $todayTs) continue;
		if ((int)date('Y', $ts) < 1900) continue;

		// Birthdate is usually the oldest plausible date on ID text.
		if ($pickedTs === null || $ts < $pickedTs) {
			$picked = $dateStr;
			$pickedTs = $ts;
		}
	}

	return $picked;
}

function id_ocr_build_pattern_set($idType) {
	$generic = [
		'last_name' => [
			'/(?:SURNAME|LAST\s*NAME)\s*[:\-]?\s*([A-Z\-\' ]{2,40})\b/',
		],
		'first_name' => [
			'/(?:GIVEN\s*NAME(?:S)?|FIRST\s*NAME)\s*[:\-]?\s*([A-Z\-\' ]{2,50})\b/',
		],
		'middle_name' => [
			'/(?:MIDDLE\s*NAME|MI)\s*[:\-]?\s*([A-Z\-\' ]{1,40})\b/',
		],
		'birthdate' => [
			'/(?:DATE\s*OF\s*BIRTH|BIRTH\s*DATE|DOB)\s*[:\-]?\s*([A-Z0-9,\/\- ]{6,30})\b/',
			'/\b((?:0?[1-9]|1[0-2])[\/\-](?:0?[1-9]|[12][0-9]|3[01])[\/\-](?:19|20)\d{2})\b/',
			'/\b((?:19|20)\d{2}[\/\-](?:0?[1-9]|1[0-2])[\/\-](?:0?[1-9]|[12][0-9]|3[01]))\b/',
		],
	];

	$passport = [
		'last_name' => [
			'/(?:SURNAME)\s*[:\-]?\s*([A-Z\-\' ]{2,50})\b/',
		],
		'first_name' => [
			'/(?:GIVEN\s*NAME(?:S)?)\s*[:\-]?\s*([A-Z\-\' ]{2,60})\b/',
		],
		'middle_name' => [],
		'birthdate' => [
			'/(?:DATE\s*OF\s*BIRTH)\s*[:\-]?\s*([A-Z0-9,\/\- ]{6,30})\b/',
		],
	];

	$driversLicense = [
		'last_name' => [
			'/(?:LAST\s*NAME|LN)\s*[:\-]?\s*([A-Z\-\' ]{2,45})\b/',
		],
		'first_name' => [
			'/(?:FIRST\s*NAME|FN|GIVEN\s*NAME)\s*[:\-]?\s*([A-Z\-\' ]{2,45})\b/',
		],
		'middle_name' => [
			'/(?:MIDDLE\s*NAME|MN|MI)\s*[:\-]?\s*([A-Z\-\' ]{1,40})\b/',
		],
		'birthdate' => [
			'/(?:BIRTH\s*DATE|DOB|DATE\s*OF\s*BIRTH)\s*[:\-]?\s*([A-Z0-9,\/\- ]{6,30})\b/',
		],
	];

	$philsys = [
		'last_name' => [
			'/(?:LAST\s*NAME|APELYIDO|SURNAME)\s*[:\-]?\s*([A-Z\-\' ]{2,50})\b/',
		],
		'first_name' => [
			'/(?:FIRST\s*NAME|PANGALAN|GIVEN\s*NAME)\s*[:\-]?\s*([A-Z\-\' ]{2,50})\b/',
		],
		'middle_name' => [
			'/(?:MIDDLE\s*NAME|GITNANG\s*PANGALAN|MI)\s*[:\-]?\s*([A-Z\-\' ]{1,40})\b/',
		],
		'birthdate' => [
			'/(?:BIRTH\s*DATE|DATE\s*OF\s*BIRTH|DOB|KAPANGANAKAN)\s*[:\-]?\s*([A-Z0-9,\/\- ]{6,30})\b/',
		],
	];

	$byType = [
		'passport' => $passport,
		'drivers_license' => $driversLicense,
		'philsys' => $philsys,
	];

	$selected = $byType[$idType] ?? [];
	$merged = [];
	foreach ($generic as $k => $patterns) {
		$typePatterns = $selected[$k] ?? [];
		$merged[$k] = array_merge($typePatterns, $patterns);
	}

	return $merged;
}

function id_ocr_extract_fields($text, $idType = 'other') {
	$flat = preg_replace('/\s+/', ' ', strtoupper((string)$text));
	$lines = id_ocr_text_lines($text);
	$patterns = id_ocr_build_pattern_set($idType);

	$lastNameRaw = id_ocr_match_line_value($flat, $patterns['last_name']);

	$firstNameRaw = id_ocr_match_line_value($flat, $patterns['first_name']);

	$middleNameRaw = id_ocr_match_line_value($flat, $patterns['middle_name']);

	$birthdateRaw = id_ocr_match_line_value($flat, $patterns['birthdate']);

	if ($lastNameRaw === '') {
		$lastNameRaw = id_ocr_extract_by_labels($lines, ['SURNAME', 'LASTNAME', 'APELYIDO']);
	}

	if ($firstNameRaw === '') {
		$firstNameRaw = id_ocr_extract_by_labels($lines, ['GIVENNAME', 'FIRSTNAME', 'PANGALAN']);
	}

	if ($middleNameRaw === '') {
		$middleNameRaw = id_ocr_extract_by_labels($lines, ['MIDDLENAME', 'MI', 'GITNANGPANGALAN']);
	}

	if ($birthdateRaw === '') {
		$birthdateRaw = id_ocr_extract_by_labels($lines, ['DATEOFBIRTH', 'BIRTHDATE', 'DOB', 'KAPANGANAKAN']);
	}

	// Fallback for IDs that print one combined NAME line.
	if ($lastNameRaw === '' && $firstNameRaw === '' && preg_match('/\bNAME\s*[:\-]?\s*([A-Z\-\' ]{5,80})\b/', $flat, $nameMatch)) {
		$full = trim((string)$nameMatch[1]);
		$parts = preg_split('/\s+/', $full);
		if (is_array($parts) && count($parts) >= 2) {
			$lastNameRaw = array_pop($parts);
			$firstNameRaw = implode(' ', $parts);
		}
	}

	$fields = [
		'last_name' => id_ocr_clean_name($lastNameRaw),
		'first_name' => id_ocr_clean_name($firstNameRaw),
		'middle_name' => id_ocr_clean_name($middleNameRaw),
		'birthdate' => id_ocr_normalize_date($birthdateRaw),
	];

	if (empty($fields['birthdate'])) {
		$fallbackDate = id_ocr_extract_birthdate_fallback($text);
		if ($fallbackDate !== null) {
			$fields['birthdate'] = $fallbackDate;
		}
	}

	foreach (array_keys($fields) as $k) {
		if ($fields[$k] === '' || $fields[$k] === null) {
			unset($fields[$k]);
		}
	}

	return $fields;
}

$fields = id_ocr_extract_fields($ocrText, $idType);

if (empty($fields)) {
	id_ocr_response(false, 'OCR completed, but no matching fields were detected.');
}

id_ocr_response(true, 'OCR extraction successful', [
	'id_type' => $idType,
	'fields' => $fields,
]);

