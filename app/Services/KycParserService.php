<?php

namespace App\Services;

use DateTimeImmutable;

class KycParserService
{
    public function parse(string $ocrText): array
    {
        $normalizedText = $this->normalizeText($ocrText);
        $lines = $this->splitLines($normalizedText);

        $parsed = [
            'name' => $this->extractName($lines, $normalizedText),
            'dob' => $this->extractDob($lines, $normalizedText),
            'address' => $this->extractAddress($lines),
            'id_number' => $this->extractIdNumber($lines, $normalizedText),
        ];

        return [
            'name' => $this->sanitizeOutput($parsed['name'], 120),
            'dob' => $this->sanitizeOutput($parsed['dob'], 20),
            'address' => $this->sanitizeOutput($parsed['address'], 255),
            'id_number' => $this->sanitizeOutput($parsed['id_number'], 40),
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        return preg_replace('/[ \t]+/', ' ', $text) ?? $text;
    }

    /**
     * @return array<int, string>
     */
    private function splitLines(string $text): array
    {
        $lines = preg_split('/\n+/', $text) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $clean[] = $line;
            }
        }

        return array_values($clean);
    }

    /**
     * @param array<int, string> $lines
     */
    private function extractName(array $lines, string $text): string
    {
        $surname = $this->extractLabeledNamePart($lines, '/\b(surname|last\s*name|family\s*name|apelyido)\b/i');
        $given = $this->extractLabeledNamePart($lines, '/\b(given\s*names?|first\s*name|mga\s*pangalan)\b/i');
        $middle = $this->extractLabeledNamePart($lines, '/\b(middle\s*name|gitnang\s*(pangalan|apelyido)?)\b/i');

        if ($surname !== '' || $given !== '' || $middle !== '') {
            $parts = [];
            if ($surname !== '') {
                $parts[] = $surname;
            }

            if ($given !== '' && strcasecmp($given, $surname) !== 0) {
                $parts[] = $given;
            }

            if ($middle !== '' && strcasecmp($middle, $surname) !== 0 && strcasecmp($middle, $given) !== 0) {
                $parts[] = $middle;
            }

            if ($surname !== '' && $given !== '') {
                $full = $surname . ', ' . $given;
                if ($middle !== '') {
                    $full .= ' ' . $middle;
                }

                return trim(preg_replace('/\s+/', ' ', $full) ?? $full);
            }

            return trim(implode(' ', $parts));
        }

        $inlineName = $this->extractLabeledNamePart($lines, '/\b(full\s*name|name)\b/i');
        if ($inlineName !== '') {
            return $inlineName;
        }

        foreach (array_slice($lines, 0, 14) as $line) {
            $candidate = $this->cleanupNameCandidate($line, 2);
            if ($candidate === '') {
                continue;
            }

            $words = preg_split('/\s+/', str_replace(',', ' ', $candidate)) ?: [];
            $wordCount = count(array_filter($words));
            if ($wordCount >= 2 && $wordCount <= 5) {
                return $candidate;
            }
        }

        if (preg_match('/\bname\b\s*[:\-]?\s*([A-Za-z][A-Za-z\s,.-]{3,})/i', $text, $match)) {
            return $this->cleanupNameCandidate((string) $match[1], 2);
        }

        return '';
    }

    /**
     * @param array<int, string> $lines
     */
    private function extractLabeledNamePart(array $lines, string $labelPattern): string
    {
        foreach ($lines as $index => $line) {
            if (!preg_match($labelPattern, $line)) {
                continue;
            }

            $sameLine = preg_replace($labelPattern, ' ', $line) ?? $line;
            $sameLine = preg_replace('/[:\-]+/', ' ', $sameLine) ?? $sameLine;

            $candidate = $this->cleanupNameCandidate($sameLine, 1);
            if ($candidate !== '') {
                return $candidate;
            }

            for ($offset = 1; $offset <= 3; $offset++) {
                $target = $lines[$index + $offset] ?? '';
                $candidate = $this->cleanupNameCandidate($target, 1);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return '';
    }

    private function cleanupNameCandidate(string $value, int $minWords): string
    {
        $candidate = preg_replace('/[^A-Za-z\s,.-]/', ' ', $value) ?? $value;
        $candidate = preg_replace('/\s+/', ' ', trim($candidate)) ?? trim($candidate);
        $candidate = trim($candidate, " ,.-");

        if ($candidate === '' || preg_match('/\d/', $candidate)) {
            return '';
        }

        if ($this->looksLikeNameNoise($candidate)) {
            return '';
        }

        $words = preg_split('/\s+/', str_replace(',', ' ', $candidate)) ?: [];
        $wordCount = count(array_filter($words));
        if ($wordCount < $minWords) {
            return '';
        }

        return $candidate;
    }

    private function looksLikeNameNoise(string $value): bool
    {
        if (preg_match('/\b(republic|department|philippine|identification\s*card|national\s*id|pambansang|authority|address|tirahan|birth|dob|date|sex|gender|signature|pcn|psn|id\s*number)\b/i', $value)) {
            return true;
        }

        return (bool) preg_match('/\b(first\s*name|middle\s*name|last\s*name|given\s*name|surname|mga\s*pangalan|gitnang|apelyido)\b/i', $value);
    }

    /**
     * @param array<int, string> $lines
     */
    private function extractDob(array $lines, string $text): string
    {
        $labelPattern = '/\b(date\s*of\s*birth|birth\s*date|dob|petsa\s*ng\s*kapanganakan)\b/i';
        $datePattern = '/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}|(?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\s+\d{1,2},?\s+\d{4})\b/i';

        foreach ($lines as $index => $line) {
            if (!preg_match($labelPattern, $line)) {
                continue;
            }

            for ($offset = -1; $offset <= 2; $offset++) {
                $target = $lines[$index + $offset] ?? '';
                if ($target === '') {
                    continue;
                }

                if (preg_match($datePattern, $target, $match)) {
                    $dob = $this->normalizeDate((string) $match[1]);
                    if ($dob !== '') {
                        return $dob;
                    }
                }
            }
        }

        if (preg_match($datePattern, $text, $match)) {
            return $this->normalizeDate((string) $match[1]);
        }

        return '';
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/[A-Za-z]/', $value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        $clean = preg_replace('/\s+/', '', $value) ?? $value;
        $formats = [
            'm/d/Y',
            'm-d-Y',
            'm/d/y',
            'm-d-y',
            'd/m/Y',
            'd-m-Y',
            'd/m/y',
            'd-m-y',
            'Y-m-d',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $clean);
            if (!$date) {
                continue;
            }

            $errors = DateTimeImmutable::getLastErrors();
            if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                continue;
            }

            return $date->format('Y-m-d');
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : '';
    }

    /**
     * @param array<int, string> $lines
     */
    private function extractAddress(array $lines): string
    {
        $addressLabel = '/\b(address|residence|home\s*address|present\s*address|permanent\s*address|tirahan)\b/i';
        $stopPattern = '/\b(name|surname|given|middle|birth|dob|sex|gender|nationality|citizenship|id|number|signature|expiry|valid|issue|pcn|psn)\b/i';

        foreach ($lines as $index => $line) {
            if (!preg_match($addressLabel, $line)) {
                continue;
            }

            $first = preg_replace('/^.*?(address|residence|home\s*address|present\s*address|permanent\s*address|tirahan)\s*[:\-]?\s*/i', '', $line) ?? '';
            $parts = [];
            $first = trim($first);
            if ($first !== '') {
                $parts[] = $first;
            }

            for ($offset = 1; $offset <= 4; $offset++) {
                $target = trim((string) ($lines[$index + $offset] ?? ''));
                if ($target === '') {
                    continue;
                }

                if (preg_match($stopPattern, $target)) {
                    break;
                }

                $parts[] = $target;
            }

            $candidate = $this->cleanupAddress(implode(', ', $parts));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $addressKeywords = '/\b(blk|block|lot|phase|subd|subdivision|purok|sitio|brgy|barangay|street|st\.?|road|rd\.?|avenue|ave\.?|city|municipality|province|region)\b/i';
        $fallback = [];

        foreach ($lines as $line) {
            if (!preg_match($addressKeywords, $line)) {
                continue;
            }

            if (preg_match($stopPattern, $line)) {
                continue;
            }

            $fallback[] = $line;
            if (count($fallback) >= 3) {
                break;
            }
        }

        return $this->cleanupAddress(implode(', ', $fallback));
    }

    private function cleanupAddress(string $value): string
    {
        $value = preg_replace('/^[\/\s,.-]*(address|tirahan)\b\s*[:\-,]?\s*/i', '', trim($value)) ?? trim($value);
        $value = preg_replace('/\s+,/', ',', $value) ?? $value;
        $value = preg_replace('/,{2,}/', ',', $value) ?? $value;
        return trim($value, " ,");
    }

    /**
     * @param array<int, string> $lines
     */
    private function extractIdNumber(array $lines, string $text): string
    {
        $labelPattern = '/\b(id\s*no\.?|id\s*number|license\s*no\.?|passport\s*no\.?|tin\s*no\.?|sss\s*no\.?|gsis\s*no\.?|prc\s*no\.?|philhealth\s*no\.?|pag-?ibig\s*(mid|no\.?)|voter\'?s?\s*(id|no\.?)|postal\s*id\s*no\.?|umid\s*no\.?|crn|pcn|psn|national\s*id\s*no\.?)\b/i';

        foreach ($lines as $index => $line) {
            if (!preg_match($labelPattern, $line)) {
                continue;
            }

            for ($offset = 0; $offset <= 4; $offset++) {
                $target = (string) ($lines[$index + $offset] ?? '');
                if ($target === '') {
                    continue;
                }

                if ($offset === 0) {
                    $target = preg_replace($labelPattern, ' ', $target) ?? $target;
                }

                $candidate = $this->findIdCandidate($target);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        $candidates = [];
        foreach ($lines as $line) {
            $candidate = $this->findIdCandidate($line);
            if ($candidate === '') {
                continue;
            }

            $candidates[$candidate] = $this->scoreIdCandidate($candidate);
        }

        if (!$candidates && $text !== '') {
            $candidate = $this->findIdCandidate($text);
            if ($candidate !== '') {
                $candidates[$candidate] = $this->scoreIdCandidate($candidate);
            }
        }

        if (!$candidates) {
            return '';
        }

        arsort($candidates);
        return (string) array_key_first($candidates);
    }

    private function findIdCandidate(string $value): string
    {
        $patterns = [
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/',
            '/\b[A-Z]\d{2}-\d{2}-\d{6}\b/i',
            '/\b[A-Z]{1,4}-?\d{2,3}-?\d{4,10}\b/i',
            '/\b\d{2}-?\d{7}-?\d\b/',
            '/\b\d{3}-?\d{3}-?\d{3}(?:-?\d{3})?\b/',
            '/\b[A-Z0-9][A-Z0-9\-\/]{6,24}\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $value, $match)) {
                continue;
            }

            $candidate = $this->normalizeIdCandidate((string) $match[0]);
            if ($candidate === '') {
                continue;
            }

            if ($this->isLikelyDateCandidate($candidate)) {
                continue;
            }

            return $candidate;
        }

        return '';
    }

    private function normalizeIdCandidate(string $value): string
    {
        $value = trim((string) preg_replace('/[^A-Za-z0-9\-\/\s]/', '', $value));
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(id|no|number)$/i', $value)) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) === 16) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4, 4) . '-' . substr($digits, 8, 4) . '-' . substr($digits, 12, 4);
        }

        $value = preg_replace('/\s+/', '', strtoupper($value)) ?? strtoupper($value);
        if (strlen($value) < 6) {
            return '';
        }

        return $value;
    }

    private function scoreIdCandidate(string $candidate): int
    {
        $digits = preg_replace('/\D+/', '', $candidate) ?? '';
        $score = (strlen($digits) * 10) + strlen($candidate);

        if (strlen($digits) === 16) {
            $score += 100;
        }

        if (preg_match('/[A-Z]/', $candidate)) {
            $score += 8;
        }

        return $score;
    }

    private function isLikelyDateCandidate(string $value): bool
    {
        return (bool) preg_match('/^\d{1,4}[\/-]\d{1,2}[\/-]\d{1,4}$/', $value);
    }

    private function sanitizeOutput(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', trim($value)) ?? trim($value);
        if ($value === '') {
            return '';
        }

        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }

        return trim($value);
    }
}
