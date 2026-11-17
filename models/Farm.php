<?php
/** Farm: one row of the farm table (belongs to a Farmer; has a soil type and an irrigation type). */
class Farm extends Model
{
    protected static string $table = 'farm';
    protected static string $primaryKey = 'farm_id';
    protected static string $title = 'Farm';

    protected static function defaults(): array
    {
        return ['farmer_id' => '', 'farm_name' => '', 'location' => '', 'area_hectares' => '', 'soil_type_id' => '', 'irrigation_type_id' => ''];
    }

    protected function rules(): void
    {
        if (!ctype_digit($this->farmer_id) || !Farmer::exists((int) $this->farmer_id)) {
            $this->addError('farmer_id', 'Select a valid farmer.');
        }
        if ($this->farm_name === '' || mb_strlen($this->farm_name) > 100) {
            $this->addError('farm_name', 'Farm name is required (maximum 100 characters).');
        }
        if ($this->location === '' || mb_strlen($this->location) > 150) {
            $this->addError('location', 'Location is required (maximum 150 characters).');
        }
        if (valid_number($this->area_hectares, 0.01, 10000) === null) {
            $this->addError('area_hectares', 'Area must be a number between 0.01 and 10,000 hectares.');
        }
        if (!Lookup::exists('soil_type', $this->soil_type_id)) {
            $this->addError('soil_type_id', 'Select a valid soil type.');
        }
        if (!Lookup::exists('irrigation_type', $this->irrigation_type_id)) {
            $this->addError('irrigation_type_id', 'Select a valid irrigation type.');
        }
        if (!$this->hasError('farmer_id') && !$this->hasError('farm_name')
            && static::fetchValue(
                'SELECT 1 FROM farm WHERE farmer_id = :f AND LOWER(farm_name) = LOWER(:n) AND farm_id <> :id',
                [':f' => (int) $this->farmer_id, ':n' => $this->farm_name, ':id' => $this->id ?? 0]
            )) {
            $this->addError('farm_name', 'This farmer already has a farm with that name.');
        }
    }

    protected function columnValues(): array
    {
        return [
            'farmer_id'          => (int) $this->farmer_id,
            'farm_name'          => $this->farm_name,
            'location'           => $this->location,
            'area_hectares'      => (float) $this->area_hectares,
            'soil_type_id'       => (int) $this->soil_type_id,
            'irrigation_type_id' => (int) $this->irrigation_type_id,
        ];
    }

    protected function auditLabel(): string
    {
        return 'farm ' . $this->farm_name;
    }

    protected static function dependentsSql(): string
    {
        return 'SELECT COUNT(*) FROM planting WHERE farm_id = :id';
    }

    // ------------------------------------------------------------ lists, search, details

    public static function sortColumns(): array
    {
        return [
            'id' => 'fa.farm_id', 'farm' => 'fa.farm_name', 'farmer' => 'fr.last_name', 'location' => 'fa.location',
            'area' => 'fa.area_hectares', 'soil' => 's.soil_type_description', 'irrigation' => 'i.irrigation_type_description',
        ];
    }

    private const FROM = 'FROM farm fa
         JOIN farmer fr ON fr.farmer_id = fa.farmer_id
         JOIN soil_type s ON s.soil_type_id = fa.soil_type_id
         JOIN irrigation_type i ON i.irrigation_type_id = fa.irrigation_type_id';

    /** Filters: q, irrigation, soil. */
    private static function filter(array $f): array
    {
        $where = [];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = "(fa.farm_name ILIKE :q OR fa.location ILIKE :q OR (fr.first_name || ' ' || fr.last_name) ILIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (($f['irrigation'] ?? '') !== '' && ctype_digit($f['irrigation'])) {
            $where[] = 'fa.irrigation_type_id = :irr';
            $params[':irr'] = (int) $f['irrigation'];
        }
        if (($f['soil'] ?? '') !== '' && ctype_digit($f['soil'])) {
            $where[] = 'fa.soil_type_id = :soil';
            $params[':soil'] = (int) $f['soil'];
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
            'SELECT fa.farm_id, fa.farm_name, fa.location, fa.area_hectares, fr.first_name, fr.last_name,
                    s.soil_type_description, i.irrigation_type_description '
            . self::FROM . " $where ORDER BY $orderBy, fa.farm_id LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /** Farms of one farmer (shown on the farmer's detail page). */
    public static function byFarmer(int $farmerId): array
    {
        return static::fetchAll(
            'SELECT fa.farm_id, fa.farm_name, fa.location, fa.area_hectares, s.soil_type_description, i.irrigation_type_description
               FROM farm fa
               JOIN soil_type s ON s.soil_type_id = fa.soil_type_id
               JOIN irrigation_type i ON i.irrigation_type_id = fa.irrigation_type_id
              WHERE fa.farmer_id = :id ORDER BY fa.farm_name',
            [':id' => $farmerId]
        );
    }

    /** Farm area in hectares, or null when the farm does not exist (used to check planted area). */
    public static function areaOf(int $farmId): ?float
    {
        $area = static::fetchValue('SELECT area_hectares FROM farm WHERE farm_id = :id', [':id' => $farmId]);
        return $area === false ? null : (float) $area;
    }

    /** Dropdown on forms: farm and owner. */
    public static function options(): array
    {
        return static::fetchAll(
            "SELECT f.farm_id AS id, f.farm_name || ' — ' || fr.last_name || ', ' || fr.first_name AS label
               FROM farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id ORDER BY f.farm_name"
        );
    }

    /** Dropdown in filters: farm name only. */
    public static function nameOptions(): array
    {
        return static::fetchAll('SELECT farm_id AS id, farm_name AS label FROM farm ORDER BY farm_name');
    }

    public static function detail(int $id): ?array
    {
        return static::fetchOne(
            'SELECT fa.*, fr.first_name, fr.last_name, fr.contact_number, s.soil_type_description, i.irrigation_type_description,
                    cu.full_name AS created_name, uu.full_name AS updated_name
               FROM farm fa
               JOIN farmer fr ON fr.farmer_id = fa.farmer_id
               JOIN soil_type s ON s.soil_type_id = fa.soil_type_id
               JOIN irrigation_type i ON i.irrigation_type_id = fa.irrigation_type_id
               LEFT JOIN users cu ON cu.user_id = fa.created_by
               LEFT JOIN users uu ON uu.user_id = fa.updated_by
              WHERE fa.farm_id = :id',
            [':id' => $id]
        );
    }
}
