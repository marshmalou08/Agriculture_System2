<?php
/** Crop: one row of the crop table (has a category and a recommended season). */
class Crop extends Model
{
    protected static string $table = 'crop';
    protected static string $primaryKey = 'crop_id';
    protected static string $title = 'Crop';

    protected static function defaults(): array
    {
        return ['crop_name' => '', 'variety' => '', 'category_id' => '', 'growing_period_days' => '', 'season_id' => ''];
    }

    protected function rules(): void
    {
        if ($this->crop_name === '' || mb_strlen($this->crop_name) > 80) {
            $this->addError('crop_name', 'Crop name is required (maximum 80 characters).');
        }
        if ($this->variety === '' || mb_strlen($this->variety) > 80) {
            $this->addError('variety', 'Variety is required (maximum 80 characters).');
        }
        if (!Lookup::exists('crop_category', $this->category_id)) {
            $this->addError('category_id', 'Select a valid crop category.');
        }
        $days = valid_number($this->growing_period_days, 1, 1500);
        if ($days === null || floor($days) != $days) {
            $this->addError('growing_period_days', 'Growing period must be a whole number of days between 1 and 1,500.');
        }
        if (!Lookup::exists('season', $this->season_id)) {
            $this->addError('season_id', 'Select a valid recommended season.');
        }
        if (!$this->hasError('crop_name') && !$this->hasError('variety')
            && static::fetchValue(
                'SELECT 1 FROM crop WHERE LOWER(crop_name) = LOWER(:n) AND LOWER(variety) = LOWER(:v) AND crop_id <> :id',
                [':n' => $this->crop_name, ':v' => $this->variety, ':id' => $this->id ?? 0]
            )) {
            $this->addError('variety', 'This crop and variety combination already exists.');
        }
    }

    protected function columnValues(): array
    {
        return [
            'crop_name'           => $this->crop_name,
            'variety'             => $this->variety,
            'category_id'         => (int) $this->category_id,
            'growing_period_days' => (int) (float) $this->growing_period_days,
            'season_id'           => (int) $this->season_id,
        ];
    }

    protected function auditLabel(): string
    {
        return 'crop ' . $this->crop_name;
    }

    protected static function dependentsSql(): string
    {
        return 'SELECT COUNT(*) FROM planting WHERE crop_id = :id';
    }

    // ------------------------------------------------------------ lists, search, details

    public static function sortColumns(): array
    {
        return [
            'id' => 'c.crop_id', 'name' => 'c.crop_name', 'variety' => 'c.variety', 'category' => 'cc.category_description',
            'period' => 'c.growing_period_days', 'season' => 'se.season_description',
        ];
    }

    private const FROM = 'FROM crop c
         JOIN crop_category cc ON cc.category_id = c.category_id
         JOIN season se ON se.season_id = c.season_id';

    /** Filters: q, category. */
    private static function filter(array $f): array
    {
        $where = [];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(c.crop_name ILIKE :q OR c.variety ILIKE :q)';
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (($f['category'] ?? '') !== '' && ctype_digit($f['category'])) {
            $where[] = 'c.category_id = :cat';
            $params[':cat'] = (int) $f['category'];
        }
        return [static::whereSql($where), $params];
    }

    public static function countMatching(array $filters): int
    {
        [$where, $params] = static::filter($filters);
        return (int) static::fetchValue('SELECT COUNT(*) ' . self::FROM . " $where", $params);
    }

    public static function search(array $filters, string $orderBy, int $limit, int $offset): array
    {
        [$where, $params] = static::filter($filters);
        return static::fetchAll(
            'SELECT c.crop_id, c.crop_name, c.variety, c.growing_period_days, cc.category_description, se.season_description '
            . self::FROM . " $where ORDER BY $orderBy, c.crop_id LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /** Dropdown: "Name (variety)". */
    public static function options(): array
    {
        return static::fetchAll("SELECT crop_id AS id, crop_name || ' (' || variety || ')' AS label FROM crop ORDER BY crop_name, variety");
    }

    public static function detail(int $id): ?array
    {
        return static::fetchOne(
            'SELECT c.*, cc.category_description, se.season_description, cu.full_name AS created_name, uu.full_name AS updated_name
               FROM crop c
               JOIN crop_category cc ON cc.category_id = c.category_id
               JOIN season se ON se.season_id = c.season_id
               LEFT JOIN users cu ON cu.user_id = c.created_by
               LEFT JOIN users uu ON uu.user_id = c.updated_by
              WHERE c.crop_id = :id',
            [':id' => $id]
        );
    }
}
