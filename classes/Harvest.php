<?php
/** Harvest: one row of the harvest table (a harvest of one planting record; has a grade and a remark). */
class Harvest extends Model
{
    protected static string $table = 'harvest';
    protected static string $primaryKey = 'harvest_id';
    protected static string $title = 'Harvest record';

    protected static function defaults(): array
    {
        return ['planting_id' => '', 'harvest_date' => date('Y-m-d'), 'quantity_kg' => '', 'selling_price_per_kg' => '',
                'grade_id' => '', 'remark_id' => ''];
    }

    protected function rules(): void
    {
        $plantDate = ctype_digit($this->planting_id) ? Planting::plantingDateOf((int) $this->planting_id) : null;
        if ($plantDate === null) {
            $this->addError('planting_id', 'Select a valid planting record.');
        }
        if (!valid_date($this->harvest_date)) {
            $this->addError('harvest_date', 'Enter a valid harvest date.');
        } elseif ($plantDate && $this->harvest_date < $plantDate) {
            $this->addError('harvest_date', 'Harvest date cannot be earlier than the planting date (' . fmt_date($plantDate) . ').');
        }
        if (valid_number($this->quantity_kg, 0.01, 99999999.99) === null) {
            $this->addError('quantity_kg', 'Quantity must be a number greater than 0.');
        }
        if (valid_number($this->selling_price_per_kg, 0.01, 100000) === null) {
            $this->addError('selling_price_per_kg', 'Selling price must be a number between 0.01 and 100,000.');
        }
        if (!Lookup::exists('quality_grade', $this->grade_id)) {
            $this->addError('grade_id', 'Select a valid quality grade.');
        }
        if (!Lookup::exists('harvest_remark', $this->remark_id)) {
            $this->addError('remark_id', 'Select a valid remark.');
        }
    }

    protected function columnValues(): array
    {
        return [
            'planting_id'          => (int) $this->planting_id,
            'harvest_date'         => $this->harvest_date,
            'quantity_kg'          => (float) $this->quantity_kg,
            'selling_price_per_kg' => (float) $this->selling_price_per_kg,
            'grade_id'             => (int) $this->grade_id,
            'remark_id'            => (int) $this->remark_id,
        ];
    }

    protected function auditLabel(): string
    {
        return 'harvest record #' . $this->id;
    }

    // ------------------------------------------------------------ lists, search, details

    public static function sortColumns(): array
    {
        return [
            'id' => 'h.harvest_id', 'farm' => 'f.farm_name', 'crop' => 'c.crop_name', 'date' => 'h.harvest_date',
            'qty' => 'h.quantity_kg', 'price' => 'h.selling_price_per_kg', 'value' => 'est_value',
            'grade' => 'g.grade_description', 'remark' => 'rm.remark_description',
        ];
    }

    private const FROM = 'FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN quality_grade g ON g.grade_id = h.grade_id
             JOIN harvest_remark rm ON rm.remark_id = h.remark_id';

    /** Filters: q, grade, remark, date_from, date_to. */
    private static function filter(array $f): array
    {
        $where = [];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(f.farm_name ILIKE :q OR c.crop_name ILIKE :q OR c.variety ILIKE :q)';
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (($f['grade'] ?? '') !== '' && ctype_digit($f['grade'])) {
            $where[] = 'h.grade_id = :grade';
            $params[':grade'] = (int) $f['grade'];
        }
        if (($f['remark'] ?? '') !== '' && ctype_digit($f['remark'])) {
            $where[] = 'h.remark_id = :remark';
            $params[':remark'] = (int) $f['remark'];
        }
        if (($f['date_from'] ?? '') !== '' && valid_date($f['date_from'])) {
            $where[] = 'h.harvest_date >= :from';
            $params[':from'] = $f['date_from'];
        }
        if (($f['date_to'] ?? '') !== '' && valid_date($f['date_to'])) {
            $where[] = 'h.harvest_date <= :to';
            $params[':to'] = $f['date_to'];
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
            'SELECT h.harvest_id, h.planting_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    (h.quantity_kg * h.selling_price_per_kg) AS est_value,
                    f.farm_name, c.crop_name, c.variety, g.grade_description, rm.remark_description '
            . self::FROM . " $where ORDER BY $orderBy, h.harvest_id DESC LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /** Total quantity (kg) and estimated value of everything that matches the filters (not only one page). */
    public static function totals(array $filters): array
    {
        [$where, $params] = static::filter($filters);
        return static::fetchOne(
            'SELECT COALESCE(SUM(h.quantity_kg),0) AS qty, COALESCE(SUM(h.quantity_kg * h.selling_price_per_kg),0) AS val '
            . self::FROM . " $where",
            $params
        );
    }

    /** Harvest records of one planting record (planting detail page). */
    public static function byPlanting(int $plantingId): array
    {
        return static::fetchAll(
            'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg, g.grade_description, rm.remark_description,
                    (h.quantity_kg * h.selling_price_per_kg) AS est_value
               FROM harvest h
               JOIN quality_grade g ON g.grade_id = h.grade_id
               JOIN harvest_remark rm ON rm.remark_id = h.remark_id
              WHERE h.planting_id = :id ORDER BY h.harvest_date DESC',
            [':id' => $plantingId]
        );
    }

    public static function detail(int $id): ?array
    {
        return static::fetchOne(
            'SELECT h.*, (h.quantity_kg * h.selling_price_per_kg) AS est_value, p.planting_date, p.farm_id, p.crop_id,
                    f.farm_name, c.crop_name, c.variety, g.grade_description, rm.remark_description,
                    cu.full_name AS created_name, uu.full_name AS updated_name
               FROM harvest h
               JOIN planting p ON p.planting_id = h.planting_id
               JOIN farm f ON f.farm_id = p.farm_id
               JOIN crop c ON c.crop_id = p.crop_id
               JOIN quality_grade g ON g.grade_id = h.grade_id
               JOIN harvest_remark rm ON rm.remark_id = h.remark_id
               LEFT JOIN users cu ON cu.user_id = h.created_by
               LEFT JOIN users uu ON uu.user_id = h.updated_by
              WHERE h.harvest_id = :id',
            [':id' => $id]
        );
    }
}
