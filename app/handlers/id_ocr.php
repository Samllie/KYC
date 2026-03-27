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
$debugEnabled = (trim((string)getenv('KYC_OCR_DEBUG')) === '1') || (trim((string)($_POST['debug'] ?? '')) === '1');

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
	$ocrLang = ($idType === 'philsys') ? 'eng+fil' : 'eng';
}

function id_ocr_run_tesseract($tesseractCmd, $inputPath, $lang, $psm) {
	$cmd = escapeshellarg($tesseractCmd)
		. ' ' . escapeshellarg($inputPath)
		. ' stdout --oem 1 --psm ' . intval($psm)
		. ' -l ' . escapeshellarg($lang)
		. ' 2>&1';

	$out = [];
	$exit = 1;
	@exec($cmd, $out, $exit);

	return [
		'exit' => $exit,
		'text' => trim(implode("\n", is_array($out) ? $out : [])),
		'lang' => $lang,
		'psm' => intval($psm),
	];
}

function id_ocr_compact_preview_lines($text, $maxLines = 15) {
	$lines = id_ocr_text_lines($text);
	if (empty($lines)) return [];
	$trimmed = array_slice($lines, 0, max(1, intval($maxLines)));
	return array_values(array_map(function ($line) {
		return mb_substr((string)$line, 0, 140);
	}, $trimmed));
}

function id_ocr_debug_payload($idType, $ocrLang, $ocrText, $attemptErrors, $fields = []) {
	return [
		'id_type' => $idType,
		'ocr_lang' => $ocrLang,
		'ocr_preview_lines' => id_ocr_compact_preview_lines($ocrText, 18),
		'attempt_errors' => array_values($attemptErrors),
		'matched_fields' => $fields,
	];
}

$ocrTexts = [];
$attemptErrors = [];

$langsToTry = [$ocrLang];
if ($ocrLang !== 'eng') {
	$langsToTry[] = 'eng';
}

foreach (array_unique($langsToTry) as $lang) {
	foreach ([6, 11] as $psm) {
		$result = id_ocr_run_tesseract($tesseractCmd, $ocrInputPath, $lang, $psm);
		if ($result['exit'] === 0 && $result['text'] !== '') {
			$ocrTexts[] = $result['text'];
		} else {
			$attemptErrors[] = "lang={$result['lang']} psm={$result['psm']}: " . substr($result['text'], 0, 140);
		}
	}
}

$ocrText = trim(implode("\n", array_unique($ocrTexts)));
if ($ocrText === '') {
	$detail = '';
	if (!empty($attemptErrors)) {
		$detail = ' Attempts: ' . implode(' | ', $attemptErrors);
	}
	$extra = [];
	if ($debugEnabled) {
		$extra['debug'] = id_ocr_debug_payload($idType, $ocrLang, '', $attemptErrors, []);
	}
	id_ocr_response(false, 'No OCR text extracted. The image may be unreadable or language data may be missing.' . $detail, $extra);
}

function id_ocr_normalize_date($value) {
	$value = trim((string)$value);
	if ($value === '') return null;

	// Support compact numeric date like YYYYMMDD.
	if (preg_match('/^(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])$/', $value, $m)) {
		return $m[1] . substr($value, 2, 2) . '-' . $m[2] . '-' . $m[3];
	}

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

function id_ocr_contains_label_word($value) {
	$upper = strtoupper((string)$value);
	return preg_match('/(SURNAME|LAST\s*NAME|GIVEN\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|DATE\s*OF\s*BIRTH|BIRTH\s*DATE|DOB|APELYIDO|PANGALAN|GITNANG|KAPANGANAKAN|SEX|NATIONALITY|ADDRESS|REPUBLIC|PILIPINAS|PHILIPPINES|NATIONAL)/', $upper) === 1;
}

function id_ocr_clean_name_candidate($value) {
	$value = strtoupper(trim((string)$value));
	$value = preg_replace('/\s+/', ' ', $value);

	// Remove trailing fragments that start with another label.
	$value = preg_replace('/\b(?:SURNAME|LAST\s*NAME|GIVEN\s*NAME(?:S)?|FIRST\s*NAME|MIDDLE\s*NAME|MI|DATE\s*OF\s*BIRTH|BIRTH\s*DATE|DOB|APELYIDO|PANGALAN|GITNANG\s*PANGALAN|KAPANGANAKAN|SEX|NATIONALITY|ADDRESS)\b.*$/', '', $value);
	$value = trim((string)$value, " \t\n\r\0\x0B:;,.|/-");
	$value = preg_replace('/[^A-Z\s\-\'\.]/', '', (string)$value);
	$value = preg_replace('/\s+/', ' ', (string)$value);
	return trim((string)$value);
}

function id_ocr_is_plausible_name($value, $minChars = 2, $maxChars = 40) {
	$value = trim((string)$value);
	if ($value === '') return false;
	if (strlen($value) < $minChars || strlen($value) > $maxChars) return false;
	if (preg_match('/\d/', $value)) return false;
	if (id_ocr_contains_label_word($value)) return false;
	if (!preg_match('/^[A-Z\s\-\'\.]+$/', $value)) return false;

	$lettersOnly = preg_replace('/[^A-Z]/', '', $value);
	if (strlen((string)$lettersOnly) < $minChars) return false;

	return true;
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
	$line = strtr($line, [
		'0' => 'O',
		'1' => 'I',
		'3' => 'E',
		'4' => 'A',
		'5' => 'S',
		'6' => 'G',
		'7' => 'T',
		'8' => 'B',
	]);
	$line = preg_replace('/[^A-Z]/', '', $line);
	return (string)$line;
}

function id_ocr_label_has_token($lineNorm, $token) {
	if ($lineNorm === '' || $token === '') return false;
	if (strpos($lineNorm, $token) !== false) return true;

	$window = strlen($token);
	$len = strlen($lineNorm);
	if ($len < 3 || $window < 3) return false;

	for ($i = 0; $i <= max(0, $len - $window); $i++) {
		$chunk = substr($lineNorm, $i, $window);
		if ($chunk === false || $chunk === '') continue;
		if (levenshtein($chunk, $token) <= 1) {
			return true;
		}
	}

	return false;
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
			if ($token !== '' && id_ocr_label_has_token($lineNorm, $token)) {
				$matched = true;
				break;
			}
		}
		if (!$matched) continue;

		if (preg_match('/[:\-]\s*(.+)$/', $line, $m)) {
			$value = id_ocr_clean_name_candidate((string)$m[1]);
			if ($value !== '' && !id_ocr_looks_like_label_line($value) && !id_ocr_contains_label_word($value)) {
				return $value;
			}
		}

		if (preg_match('/\b(?:SURNAME|LAST\s*NAME|GIVEN\s*NAME(?:S)?|FIRST\s*NAME|MIDDLE\s*NAME|MI|APELYIDO|PANGALAN|KAPANGANAKAN|DOB|DATE\s*OF\s*BIRTH|BIRTH\s*DATE)\b\s*(.+)$/', $line, $m)) {
			$value = id_ocr_clean_name_candidate((string)$m[1]);
			if ($value !== '' && !id_ocr_looks_like_label_line($value) && !id_ocr_contains_label_word($value)) {
				return $value;
			}
		}

		$next = isset($lines[$i + 1]) ? trim((string)$lines[$i + 1]) : '';
		$nextClean = id_ocr_clean_name_candidate($next);
		if ($nextClean !== '' && !id_ocr_looks_like_label_line($nextClean) && !id_ocr_contains_label_word($nextClean)) {
			return $nextClean;
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
		'/\b((?:0?[1-9]|[12][0-9]|3[01])[.](?:0?[1-9]|1[0-2])[.](?:19|20)\d{2})\b/',
		'/\b((?:19|20)\d{2}[.](?:0?[1-9]|1[0-2])[.](?:0?[1-9]|[12][0-9]|3[01]))\b/',
		'/\b((?:19|20)\d{2}\s(?:0?[1-9]|1[0-2])\s(?:0?[1-9]|[12][0-9]|3[01]))\b/',
		'/\b((?:0?[1-9]|1[0-2])\s(?:0?[1-9]|[12][0-9]|3[01])\s(?:19|20)\d{2})\b/',
		'/\b((?:19|20)\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12][0-9]|3[01]))\b/',
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

function id_ocr_is_name_candidate_line($line) {
	$line = trim((string)$line);
	if ($line === '') return false;
	if (strlen($line) < 4 || strlen($line) > 80) return false;

	$upper = strtoupper($line);
	if (preg_match('/\d/', $upper)) return false;
	if (preg_match('/(REPUBLIC|PILIPINAS|NATIONAL|IDENTIFICATION|CARD|SEX|ADDRESS|BIRTH|DATE|ISSUE|EXPIRY|SIGNATURE)/', $upper)) return false;
    if (!preg_match('/^[A-Z\s\-\'\.]+$/', $upper)) return false;

	$words = preg_split('/\s+/', $upper);
	$goodWords = 0;
	foreach ($words as $w) {
		if (strlen($w) >= 2) $goodWords++;
	}

	return $goodWords >= 2;
}

function id_ocr_extract_name_fallback($lines) {
	if (!is_array($lines) || empty($lines)) return [null, null, null];

	$candidates = [];
	foreach ($lines as $line) {
		if (id_ocr_is_name_candidate_line($line)) {
			$candidates[] = trim((string)$line);
		}
	}

	$candidates = array_values(array_unique($candidates));
	if (empty($candidates)) return [null, null, null];

	$best = $candidates[0];

	// Prefer lines with comma format: LAST, FIRST MIDDLE
	foreach ($candidates as $line) {
		if (strpos($line, ',') !== false) {
			$best = $line;
			break;
		}
	}

	if (strpos($best, ',') !== false) {
		$partsByComma = array_map('trim', explode(',', $best, 2));
		$last = id_ocr_clean_name_candidate($partsByComma[0] ?? '');
		$right = id_ocr_clean_name_candidate($partsByComma[1] ?? '');
		if (id_ocr_is_plausible_name($last, 2, 45) && $right !== '') {
			$rightParts = preg_split('/\s+/', $right);
			if (is_array($rightParts) && count($rightParts) >= 1) {
				$first = array_shift($rightParts);
				$middle = count($rightParts) ? implode(' ', $rightParts) : null;
				if (id_ocr_is_plausible_name($first, 2, 35)) {
					if ($middle !== null && !id_ocr_is_plausible_name($middle, 1, 35)) {
						$middle = null;
					}
					return [$first, $last, $middle];
				}
			}
		}
	}

	$parts = preg_split('/\s+/', $best);
	if (!is_array($parts) || count($parts) < 2) return [null, null, null];

	$last = array_pop($parts);
	$first = array_shift($parts);
	$middle = count($parts) ? implode(' ', $parts) : null;

	return [$first, $last, $middle];
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

	if ($lastNameRaw === '' && $firstNameRaw === '') {
		[$fallbackFirst, $fallbackLast, $fallbackMiddle] = id_ocr_extract_name_fallback($lines);
		if ($fallbackFirst !== null && $fallbackLast !== null) {
			$firstNameRaw = $fallbackFirst;
			$lastNameRaw = $fallbackLast;
			if ($middleNameRaw === '' && $fallbackMiddle !== null) {
				$middleNameRaw = $fallbackMiddle;
			}
		}
	}

	$fields = [
		'last_name' => id_ocr_clean_name(id_ocr_clean_name_candidate($lastNameRaw)),
		'first_name' => id_ocr_clean_name(id_ocr_clean_name_candidate($firstNameRaw)),
		'middle_name' => id_ocr_clean_name(id_ocr_clean_name_candidate($middleNameRaw)),
		'birthdate' => id_ocr_normalize_date($birthdateRaw),
	];

	if (!empty($fields['last_name']) && !id_ocr_is_plausible_name($fields['last_name'], 2, 45)) {
		unset($fields['last_name']);
	}
	if (!empty($fields['first_name']) && !id_ocr_is_plausible_name($fields['first_name'], 2, 45)) {
		unset($fields['first_name']);
	}
	if (!empty($fields['middle_name']) && !id_ocr_is_plausible_name($fields['middle_name'], 1, 45)) {
		unset($fields['middle_name']);
	}

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
	$extra = [];
	if ($debugEnabled) {
		$extra['debug'] = id_ocr_debug_payload($idType, $ocrLang, $ocrText, $attemptErrors, []);
	}
	id_ocr_response(false, 'OCR completed, but no matching fields were detected.', $extra);
}

id_ocr_response(true, 'OCR extraction successful', array_merge([
	'id_type' => $idType,
	'fields' => $fields,
], $debugEnabled ? ['debug' => id_ocr_debug_payload($idType, $ocrLang, $ocrText, $attemptErrors, $fields)] : []));

