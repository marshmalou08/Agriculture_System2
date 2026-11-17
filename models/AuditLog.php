<?php
/**
 * AuditLog
 * Writes one row to the audit_log table for every create, update, delete and login.
 */
final class AuditLog
{
    public static function record(string $action, string $table, ?int $recordId = null, ?string $details = null): void
    {
        try {
            $st = Database::connection()->prepare(
                'INSERT INTO audit_log (user_id, action, table_name, record_id, details)
                 VALUES (:u, :a, :t, :r, :d)'
            );
            $st->execute([
                ':u' => current_user()['user_id'] ?? null,
                ':a' => $action,
                ':t' => $table,
                ':r' => $recordId,
                ':d' => $details,
            ]);
        } catch (PDOException $e) {
            error_log('UmaLink audit failed: ' . $e->getMessage());
        }
    }
}
