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
    function clientActivityCalculateState(?string $lastTransactionDate, ?DateTimeInterface $now = null): array
    {
        $referenceDate = clientActivityParseDate($lastTransactionDate);
        $nowDate = $now ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable('now');

        if (!$referenceDate) {
            return [
                'activity_status' => 'active',
                'activity_status_label' => 'Active',
                'activity_status_class' => 'active',
                'inactive_at' => null,
                'deactivated_at' => null,
                'next_change_at' => null,
                'countdown_display' => 'Set last transaction date',
            ];
        }

        $inactiveAt = $referenceDate->add(new DateInterval('P18M'));
        $deactivatedAt = $referenceDate->add(new DateInterval('P2Y'));

        if ($nowDate >= $deactivatedAt) {
            return [
                'activity_status' => 'deactivated',
                'activity_status_label' => 'Deactivated',
                'activity_status_class' => 'deactivated',
                'inactive_at' => $inactiveAt,
                'deactivated_at' => $deactivatedAt,
                'next_change_at' => null,
                'countdown_display' => 'Deactivated',
            ];
        }

        if ($nowDate >= $inactiveAt) {
            return [
                'activity_status' => 'inactive',
                'activity_status_label' => 'Inactive',
                'activity_status_class' => 'inactive',
                'inactive_at' => $inactiveAt,
                'deactivated_at' => $deactivatedAt,
                'next_change_at' => $deactivatedAt,
                'countdown_display' => 'Deactivated in ' . clientActivityFormatDuration($nowDate, $deactivatedAt),
            ];
        }

        return [
            'activity_status' => 'active',
            'activity_status_label' => 'Active',
            'activity_status_class' => 'active',
            'inactive_at' => $inactiveAt,
            'deactivated_at' => $deactivatedAt,
            'next_change_at' => $inactiveAt,
            'countdown_display' => 'Inactive in ' . clientActivityFormatDuration($nowDate, $inactiveAt),
        ];
    }
}

if (!function_exists('clientActivityBuildSnapshot')) {
    function clientActivityBuildSnapshot(array $row, ?DateTimeInterface $now = null): array
    {
        $state = clientActivityCalculateState($row['last_transaction_date'] ?? null, $now);
        $lastTransaction = clientActivityParseDate($row['last_transaction_date'] ?? null);
        $updatedAt = trim((string)($row['activity_status_updated_at'] ?? ''));

        return [
            'last_transaction_date_display' => $lastTransaction ? $lastTransaction->format('M j, Y') : 'Not set',
            'last_transaction_date_value' => $lastTransaction ? $lastTransaction->format('Y-m-d') : '',
            'activity_status_display' => $state['activity_status_label'],
            'activity_status_class' => $state['activity_status_class'],
            'activity_countdown_display' => $state['countdown_display'],
            'activity_inactive_date_display' => $state['inactive_at'] instanceof DateTimeInterface ? $state['inactive_at']->format('M j, Y') : 'N/A',
            'activity_deactivated_date_display' => $state['deactivated_at'] instanceof DateTimeInterface ? $state['deactivated_at']->format('M j, Y') : 'N/A',
            'activity_next_change_display' => $state['next_change_at'] instanceof DateTimeInterface ? $state['next_change_at']->format('M j, Y') : 'N/A',
            'activity_status_updated_display' => $updatedAt !== '' ? date('M j, Y g:i A', strtotime($updatedAt)) : 'N/A',
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
            || !clientActivityHasColumn($db, $tableSafe, 'last_transaction_date')
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status')
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status_updated_at')
        ) {
            return;
        }

        $selectSql = "SELECT `$idColumnSafe` AS record_id, last_transaction_date, activity_status, activity_status_updated_at FROM `$tableSafe` WHERE `$idColumnSafe` = ? LIMIT 1";
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

        $lastTransaction = clientActivityParseDate($row['last_transaction_date'] ?? null);
        $existingStatus = strtolower(trim((string)($row['activity_status'] ?? '')));
        $existingUpdatedAt = trim((string)($row['activity_status_updated_at'] ?? ''));

        if (!$lastTransaction) {
            if ($existingStatus !== 'active' || $existingUpdatedAt === '') {
                $activeSql = "UPDATE `$tableSafe` SET activity_status = 'active', activity_status_updated_at = ? WHERE `$idColumnSafe` = ?";
                $activeStmt = $db->prepare($activeSql);
                if ($activeStmt) {
                    $updatedAt = date('Y-m-d H:i:s');
                    $activeStmt->bind_param('si', $updatedAt, $recordIdValue);
                    $activeStmt->execute();
                    $activeStmt->close();
                }
            }

            return;
        }

        $calculated = clientActivityCalculateState($row['last_transaction_date'] ?? null);
        $calculatedStatus = $calculated['activity_status'] ?? null;

        if ($calculatedStatus === null) {
            return;
        }

        $shouldUpdate = $forceTimestamp || $existingStatus !== $calculatedStatus || $existingUpdatedAt === '';
        if (!$shouldUpdate) {
            return;
        }

        $updatedAt = date('Y-m-d H:i:s');
        $updateSql = "UPDATE `$tableSafe` SET activity_status = ?, activity_status_updated_at = ? WHERE `$idColumnSafe` = ?";
        $updateStmt = $db->prepare($updateSql);
        if (!$updateStmt) {
            return;
        }

        $updateStmt->bind_param('ssi', $calculatedStatus, $updatedAt, $recordIdValue);
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
            || !clientActivityHasColumn($db, $tableSafe, 'last_transaction_date')
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status')
            || !clientActivityHasColumn($db, $tableSafe, 'activity_status_updated_at')
        ) {
            return;
        }

        $selectSql = "SELECT `$idColumnSafe` AS record_id, last_transaction_date, activity_status, activity_status_updated_at FROM `$tableSafe`";
        $result = $db->query($selectSql);
        if (!$result instanceof mysqli_result) {
            return;
        }

        $updateSql = "UPDATE `$tableSafe` SET activity_status = ?, activity_status_updated_at = ? WHERE `$idColumnSafe` = ?";
        $clearSql = "UPDATE `$tableSafe` SET activity_status = NULL, activity_status_updated_at = NULL WHERE `$idColumnSafe` = ?";
        $updateStmt = $db->prepare($updateSql);
        $clearStmt = $db->prepare($clearSql);

        while ($row = $result->fetch_assoc()) {
            $recordIdValue = intval($row['record_id'] ?? 0);
            if ($recordIdValue <= 0) {
                continue;
            }

            $lastTransaction = clientActivityParseDate($row['last_transaction_date'] ?? null);
            $existingStatus = strtolower(trim((string)($row['activity_status'] ?? '')));
            $existingUpdatedAt = trim((string)($row['activity_status_updated_at'] ?? ''));

            if (!$lastTransaction) {
                if ($existingStatus !== 'active' || $existingUpdatedAt === '') {
                    if ($updateStmt) {
                        $updatedAt = date('Y-m-d H:i:s');
                        $activeStatus = 'active';
                        $updateStmt->bind_param('ssi', $activeStatus, $updatedAt, $recordIdValue);
                        $updateStmt->execute();
                    }
                }
                continue;
            }

            $calculated = clientActivityCalculateState($row['last_transaction_date'] ?? null);
            $calculatedStatus = $calculated['activity_status'] ?? null;
            if ($calculatedStatus === null) {
                continue;
            }

            $shouldUpdate = $existingStatus !== $calculatedStatus || $existingUpdatedAt === '';
            if (!$shouldUpdate || !$updateStmt) {
                continue;
            }

            $updatedAt = date('Y-m-d H:i:s');
            $updateStmt->bind_param('ssi', $calculatedStatus, $updatedAt, $recordIdValue);
            $updateStmt->execute();
        }

        $result->free();

        if ($updateStmt) {
            $updateStmt->close();
        }
        if ($clearStmt) {
            $clearStmt->close();
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
        $dateValue = trim((string)$value);
        if ($dateValue === '') {
            return 'N/A';
        }

        try {
            return (new DateTimeImmutable($dateValue))->format('M j, Y g:i A');
        } catch (Throwable $e) {
            return 'N/A';
        }
    }
}
