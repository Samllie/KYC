<?php

if (!function_exists('clientActivityTableExists')) {
    function clientActivityTableExists(mysqli $db, string $table): bool
    {
        static $cache = [];

        $cacheKey = $table;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($tableSafe === '') {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt = $db->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = ?
             LIMIT 1"
        );
        if (!$stmt) {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt->bind_param('s', $tableSafe);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result instanceof mysqli_result && $result->num_rows > 0;
        $stmt->close();

        $cache[$cacheKey] = $exists;
        return $exists;
    }
}

if (!function_exists('clientActivityHasColumn')) {
    function clientActivityHasColumn(mysqli $db, string $table, string $column): bool
    {
        static $cache = [];

        $cacheKey = $table . '::' . $column;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        if ($tableSafe === '' || $columnSafe === '') {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt = $db->prepare(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND column_name = ?
             LIMIT 1"
        );
        if (!$stmt) {
            $cache[$cacheKey] = false;
            return false;
        }

        $stmt->bind_param('ss', $tableSafe, $columnSafe);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result instanceof mysqli_result && $result->num_rows > 0;
        $stmt->close();

        $cache[$cacheKey] = $exists;
        return $exists;
    }
}

if (!function_exists('clientActivityParseDate')) {
    function clientActivityParseDate(?string $value): ?DateTimeImmutable
    {
        $dateValue = trim((string)$value);
        if ($dateValue === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($dateValue, 0, 10));
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        try {
            return (new DateTimeImmutable($dateValue))->setTime(0, 0, 0);
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('clientActivityFormatDuration')) {
    function clientActivityFormatDuration(DateTimeInterface $from, DateTimeInterface $to): string
    {
        $start = DateTimeImmutable::createFromInterface($from);
        $end = DateTimeImmutable::createFromInterface($to);

        if ($end <= $start) {
            return '0 days';
        }

        $interval = $start->diff($end);
        $parts = [];

        if ($interval->y > 0) {
            $parts[] = $interval->y . ' year' . ($interval->y === 1 ? '' : 's');
        }
        if ($interval->m > 0) {
            $parts[] = $interval->m . ' month' . ($interval->m === 1 ? '' : 's');
        }
        if ($interval->d > 0) {
            $parts[] = $interval->d . ' day' . ($interval->d === 1 ? '' : 's');
        }

        if (empty($parts)) {
            return '0 days';
        }

        return implode(', ', array_slice($parts, 0, 2));
    }
}

if (!function_exists('clientActivityCalculateState')) {
    function clientActivityCalculateState(?string $activityStatus, ?DateTimeInterface $now = null): array
    {
        $normalizedStatus = strtolower(trim((string)$activityStatus));
        if ($normalizedStatus !== 'inactive' && $normalizedStatus !== 'deactivated') {
            return [
                'activity_status' => 'active',
                'activity_status_label' => 'Active',
                'activity_status_class' => 'active',
                'inactive_at' => null,
                'deactivated_at' => null,
                'next_change_at' => null,
                'countdown_display' => '',
            ];
        }

        if ($normalizedStatus === 'deactivated') {
            return [
                'activity_status' => 'deactivated',
                'activity_status_label' => 'Deactivated',
                'activity_status_class' => 'deactivated',
                'inactive_at' => null,
                'deactivated_at' => null,
                'next_change_at' => null,
                'countdown_display' => '',
            ];
        }

        return [
            'activity_status' => 'inactive',
            'activity_status_label' => 'Inactive',
            'activity_status_class' => 'inactive',
            'inactive_at' => null,
            'deactivated_at' => null,
            'next_change_at' => null,
            'countdown_display' => '',
        ];
    }
}

if (!function_exists('clientActivityBuildSnapshot')) {
    function clientActivityBuildSnapshot(array $row, ?DateTimeInterface $now = null): array
    {
        $state = clientActivityCalculateState($row['activity_status'] ?? null, $now);
        $updatedAt = trim((string)($row['activity_status_updated_at'] ?? ''));

        return [
            'activity_status_display' => $state['activity_status_label'],
            'activity_status_class' => $state['activity_status_class'],
            'activity_countdown_display' => '',
            'activity_inactive_date_display' => 'N/A',
            'activity_deactivated_date_display' => 'N/A',
            'activity_next_change_display' => 'N/A',
            'activity_status_updated_display' => clientActivityFormatDate($updatedAt),
        ];
    }
}

if (!function_exists('clientActivityRefreshRow')) {
    function clientActivityRefreshRow(mysqli $db, string $table, int $recordId, string $idColumn = 'client_id', bool $forceTimestamp = false): void
    {
        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $idColumnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $idColumn);

        if ($tableSafe === '' || $idColumnSafe === '') {
            return;
        }

        if (!clientActivityTableExists($db, $tableSafe)
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status')
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status_updated_at')
        ) {
            return;
        }

        $selectSql = "SELECT `$idColumnSafe` AS record_id, activity_status, activity_status_updated_at FROM `$tableSafe` WHERE `$idColumnSafe` = ? LIMIT 1";
        $stmt = $db->prepare($selectSql);
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return;
        }

        $recordIdValue = intval($row['record_id'] ?? 0);
        if ($recordIdValue <= 0) {
            return;
        }

        $storedStatus = strtolower(trim((string)($row['activity_status'] ?? '')));
        $normalizedStatus = $storedStatus === 'inactive' || $storedStatus === 'deactivated'
            ? $storedStatus
            : 'active';
        $existingUpdatedAt = trim((string)($row['activity_status_updated_at'] ?? ''));
        $shouldUpdate = $forceTimestamp || $storedStatus !== $normalizedStatus || $existingUpdatedAt === '';

        if (!$shouldUpdate) {
            return;
        }

        $updatedAt = date('Y-m-d H:i:s');
        $updateSql = "UPDATE `$tableSafe` SET activity_status = ?, activity_status_updated_at = ? WHERE `$idColumnSafe` = ?";
        $updateStmt = $db->prepare($updateSql);
        if (!$updateStmt) {
            return;
        }

        $updateStmt->bind_param('ssi', $normalizedStatus, $updatedAt, $recordIdValue);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

if (!function_exists('clientActivityRefreshTable')) {
    function clientActivityRefreshTable(mysqli $db, string $table, string $idColumn = 'client_id'): void
    {
        $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $idColumnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $idColumn);

        if ($tableSafe === '' || $idColumnSafe === '') {
            return;
        }

        if (!clientActivityTableExists($db, $tableSafe)
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status')
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status_updated_at')
        ) {
            return;
        }

        $selectSql = "SELECT `$idColumnSafe` AS record_id, activity_status, activity_status_updated_at FROM `$tableSafe`";
        $result = $db->query($selectSql);
        if (!$result instanceof mysqli_result) {
            return;
        }

        $updateSql = "UPDATE `$tableSafe` SET activity_status = ?, activity_status_updated_at = ? WHERE `$idColumnSafe` = ?";
        $updateStmt = $db->prepare($updateSql);

        while ($row = $result->fetch_assoc()) {
            $recordIdValue = intval($row['record_id'] ?? 0);
            if ($recordIdValue <= 0) {
                continue;
            }

            $storedStatus = strtolower(trim((string)($row['activity_status'] ?? '')));
            $normalizedStatus = $storedStatus === 'inactive' || $storedStatus === 'deactivated'
                ? $storedStatus
                : 'active';
            $existingUpdatedAt = trim((string)($row['activity_status_updated_at'] ?? ''));
            $shouldUpdate = $storedStatus !== $normalizedStatus || $existingUpdatedAt === '';

            if (!$shouldUpdate || !$updateStmt) {
                continue;
            }

            $updatedAt = date('Y-m-d H:i:s');
            $updateStmt->bind_param('ssi', $normalizedStatus, $updatedAt, $recordIdValue);
            $updateStmt->execute();
        }

        $result->free();

        if ($updateStmt) {
            $updateStmt->close();
        }
    }
}

if (!function_exists('clientActivityFormatDate')) {
    function clientActivityFormatDate(?string $value): string
    {
        $date = clientActivityParseDate($value);
        return $date ? $date->format('M j, Y') : 'N/A';
    }
}

if (!function_exists('clientActivityFormatDateTime')) {
    function clientActivityFormatDateTime(?string $value): string
    {
        return clientActivityFormatDate($value);
    }
}
