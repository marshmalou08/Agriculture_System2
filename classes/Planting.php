<?php
/** Planting: one row of the planting table (a crop planted on a farm; has a status). */
class Planting extends Model
{
    protected static string $table = 'planting';
    protected static string $primaryKey = 'planting_id';
    protected static string $title = 'Planting record';

    protected static function defaults(): array
    {
        return ['farm_id' => '', 'crop_id' => '', 'planting_date' => date('Y-m-d'), 'area_planted' => '', 'seed_quantity' => '',
                'expected_harvest_date' => '', 'status_id' => '1'];
    }

    protected function rules(): void
    {
        $farmArea = null;
        if (!ctype_digit($this->farm_id)) {
            $this->addError('farm_id', 'Select a valid farm.');
        } else {
            $farmArea = Farm::areaOf((int) $this->farm_id);
            if ($farmArea === null) {
                $this->addError('farm_id', 'Select a valid farm.');
            }
        }
        if (!ctype_digit($this->crop_id) || !Crop::exists((int) $this->crop_id)) {
            $this->addError('crop_id', 'Select a valid crop.');
        }
        if (!valid_date($this->planting_date)) {
            $this->addError('planting_date', 'Enter a valid planting date.');
        }
        $area = valid_number($this->area_planted, 0.01, 10000);
        if ($area === null) {
            $this->addError('area_planted', 'Area planted must be a number greater than 0 (maximum 10,000).');
        } elseif ($farmArea !== null && $area > $farmArea) {
            $this->addError('area_planted', 'Area planted (' . number_format($area, 2) . ' ha) cannot exceed the farm area (' . number_format($farmArea, 2) . ' ha).');
        }
        if (valid_number($this->seed_quantity, 0.01, 999999.99) === null) {
            $this->addError('seed_quantity', 'Seed quantity must be a number greater than 0.');
        }
        if (!valid_date($this->expected_harvest_date)) {
            $this->addError('expected_harvest_date', 'Enter a valid expected harvest date.');
        } elseif (!$this->hasError('planting_date') && $this->expected_harvest_date <= $this->planting_date) {
            $this->addError('expected_harvest_date', 'Expected harvest date must be after the planting date.');
        }
        if (!Lookup::exists('planting_status', $this->status_id)) {
            $this->addError('status_id', 'Select a valid status.');
        }
    }

    protected function columnValues(): array
    {
        return [
            'farm_id'               => (int) $this->farm_id,
            'crop_id'               => (int) $this->crop_id,
            'planting_date'         => $this->planting_date,
            'area_planted'          => (float) $this->area_planted,
            'seed_quantity'         => (float) $this->seed_quantity,
            'expected_harvest_date' => $this->expected_harvest_date,
            'status_id'             => (int) $this->status_id,
        ];
    }

    protected function auditLabel(): string
    {
        return 'planting record #' . $this->id;
    }

    protected static function dependentsSql(): string
    {
        return 'SELECT COUNT(*) FROM harvest WHERE planting_id = :id';
    }

    // ------------------------------------------------------------ lists, search, details

    public static function sortColumns(): array
    {
        return [
            'id' => 'p.planting_id', 'farm' => 'f.farm_name', 'crop' => 'c.crop_name', 'date' => 'p.planting_date',
            'area' => 'p.area_planted', 'seed' => 'p.seed_quantity', 'expected' => 'p.expected_harvest_date',
            'status' => 'ps.status_description',
        ];
    }

    private const FROM = 'FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN planting_status ps ON ps.status_id = p.status_id';

    /** Filters: q, status, farm, crop, date_from, date_to. */
    private static function filter(array $f): array
    {
        $where = [];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = "(f.farm_name ILIKE :q OR c.crop_name ILIKE :q OR c.variety ILIKE :q OR (fr.first_name || ' ' || fr.last_name) ILIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (($f['status'] ?? '') !== '' && ctype_digit($f['status'])) {
            $where[] = 'p.status_id = :status';
            $params[':status'] = (int) $f['status'];
        }
        if (($f['farm'] ?? '') !== '' && ctype_digit($f['farm'])) {
            $where[] = 'p.farm_id = :farm';
            $params[':farm'] = (int) $f['farm'];
        }
        if (($f['crop'] ?? '') !== '' && ctype_digit($f['crop'])) {
            $where[] = 'p.crop_id = :crop';
            $params[':crop'] = (int) $f['crop'];
        }
        if (($f['date_from'] ?? '') !== '' && valid_date($f['date_from'])) {
            $where[] = 'p.planting_date >= :from';
            $params[':from'] = $f['date_from'];
        }
        if (($f['date_to'] ?? '') !== '' && valid_date($f['date_to'])) {
            $where[] = 'p.planting_date <= :to';
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
            'SELECT p.planting_id, p.planting_date, p.area_planted, p.seed_quantity, p.expected_harvest_date, p.status_id,
                    f.farm_name, c.crop_name, c.variety, ps.status_description '
            . self::FROM . " $where ORDER BY $orderBy, p.planting_id DESC LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /** Planting records on one farm (farm detail page). */
    public static function byFarm(int $farmId): array
    {
        return static::fetchAll(
            'SELECT p.planting_id, p.planting_date, p.area_planted, p.expected_harvest_date, c.crop_name, c.variety,
                    s.status_description, p.status_id
               FROM planting p
               JOIN crop c ON c.crop_id = p.crop_id
               JOIN planting_status s ON s.status_id = p.status_id
              WHERE p.farm_id = :id ORDER BY p.planting_date DESC',
            [':id' => $farmId]
        );
    }

    /** Planting records of one crop (crop detail page). */
    public static function byCrop(int $cropId): array
    {
        return static::fetchAll(
            'SELECT p.planting_id, p.planting_date, p.area_planted, f.farm_name, s.status_description, p.status_id
               FROM planting p
               JOIN farm f ON f.farm_id = p.farm_id
               JOIN planting_status s ON s.status_id = p.status_id
              WHERE p.crop_id = :id ORDER BY p.planting_date DESC',
            [':id' => $cropId]
        );
    }

    /** Planting date of a record, or null when it does not exist (used to check the harvest date). */
    public static function plantingDateOf(int $plantingId): ?string
    {
        $date = static::fetchValue('SELECT planting_date FROM planting WHERE planting_id = :id', [':id' => $plantingId]);
        return $date === false ? null : (string) $date;
    }

    /** Dropdown on the harvest form. */
    public static function options(): array
    {
        return static::fetchAll(
            "SELECT p.planting_id AS id,
                    '#' || p.planting_id || ' — ' || c.crop_name || ' at ' || f.farm_name || ' (planted ' || to_char(p.planting_date, 'Mon DD, YYYY') || ')' AS label
               FROM planting p JOIN farm f ON f.farm_id = p.farm_id JOIN crop c ON c.crop_id = p.crop_id
              ORDER BY p.planting_id"
        );
    }

    public static function detail(int $id): ?array
    {
        return static::fetchOne(
            'SELECT p.*, f.farm_name, fr.first_name, fr.last_name, c.crop_name, c.variety, ps.status_description,
                    cu.full_name AS created_name, uu.full_name AS updated_name
               FROM planting p
               JOIN farm f ON f.farm_id = p.farm_id
               JOIN farmer fr ON fr.farmer_id = f.farmer_id
               JOIN crop c ON c.crop_id = p.crop_id
               JOIN planting_status ps ON ps.status_id = p.status_id
               LEFT JOIN users cu ON cu.user_id = p.created_by
               LEFT JOIN users uu ON uu.user_id = p.updated_by
              WHERE p.planting_id = :id',
            [':id' => $id]
        );
    }
}
