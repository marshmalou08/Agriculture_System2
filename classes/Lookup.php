<?php
/**
 * Lookup
 * Reads the small lookup tables (soil type, irrigation type, crop category, season,
 * planting status, quality grade, harvest remark, role) used by the dropdowns.
 * Only the tables in $tables can be used, so no user input ever reaches the SQL.
 */
final class Lookup
{
    /** table => [id column, description column] */
    private const TABLES = [
        'soil_type'       => ['soil_type_id', 'soil_type_description'],
        'irrigation_type' => ['irrigation_type_id', 'irrigation_type_description'],
        'crop_category'   => ['category_id', 'category_description'],
        'season'          => ['season_id', 'season_description'],
        'planting_status' => ['status_id', 'status_description'],
        'quality_grade'   => ['grade_id', 'grade_description'],
        'harvest_remark'  => ['remark_id', 'remark_description'],
        'role'            => ['role_id', 'role_description'],
    ];

    /** Rows of [id, label] for a dropdown. */
    public static function options(string $table): array
    {
        [$id, $label] = self::columns($table);
        return Database::connection()
            ->query("SELECT $id AS id, $label AS label FROM $table ORDER BY $id")
            ->fetchAll();
    }

    /** True when the id exists in the lookup table (checks a submitted dropdown value). */
    public static function exists(string $table, $id): bool
    {
        if (!is_string($id) && !is_int($id)) {
            return false;
        }
        if (!ctype_digit((string) $id)) {
            return false;
        }
        [$idCol] = self::columns($table);
        $st = Database::connection()->prepare("SELECT 1 FROM $table WHERE $idCol = :id");
        $st->execute([':id' => (int) $id]);
        return (bool) $st->fetchColumn();
    }

    private static function columns(string $table): array
    {
        if (!isset(self::TABLES[$table])) {
            throw new InvalidArgumentException("Unknown lookup table: $table");
        }
        return self::TABLES[$table];
    }
}
