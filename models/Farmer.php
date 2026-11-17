<?php
/** Farmer: one row of the farmer table. */
class Farmer extends Model
{
    protected static string $table = 'farmer';
    protected static string $primaryKey = 'farmer_id';
    protected static string $title = 'Farmer';

    protected static function defaults(): array
    {
        return ['first_name' => '', 'last_name' => '', 'contact_number' => '', 'address' => '', 'date_registered' => date('Y-m-d')];
    }

    protected function rules(): void
    {
        if ($this->first_name === '' || mb_strlen($this->first_name) > 50) {
            $this->addError('first_name', 'First name is required (maximum 50 characters).');
        }
        if ($this->last_name === '' || mb_strlen($this->last_name) > 50) {
            $this->addError('last_name', 'Last name is required (maximum 50 characters).');
        }
        if (!preg_match('/^[0-9+() -]{7,20}$/', $this->contact_number)) {
            $this->addError('contact_number', 'Enter a valid contact number (7-20 digits; + ( ) - allowed).');
        } elseif (static::fetchValue(
            'SELECT 1 FROM farmer WHERE contact_number = :c AND farmer_id <> :id',
            [':c' => $this->contact_number, ':id' => $this->id ?? 0]
        )) {
            $this->addError('contact_number', 'This contact number is already registered to another farmer.');
        }
        if ($this->address === '' || mb_strlen($this->address) > 150) {
            $this->addError('address', 'Address is required (maximum 150 characters).');
        }
        if (!valid_date($this->date_registered)) {
            $this->addError('date_registered', 'Enter a valid registration date.');
        } elseif ($this->date_registered > date('Y-m-d')) {
            $this->addError('date_registered', 'Registration date cannot be in the future.');
        }
    }

    protected function columnValues(): array
    {
        return [
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'contact_number'  => $this->contact_number,
            'address'         => $this->address,
            'date_registered' => $this->date_registered,
        ];
    }

    protected function auditLabel(): string
    {
        return 'farmer ' . $this->first_name . ' ' . $this->last_name;
    }

    protected static function dependentsSql(): string
    {
        return 'SELECT COUNT(*) FROM farm WHERE farmer_id = :id';
    }

    // ------------------------------------------------------------ lists, search, details

    /** Allowed sort keys => SQL column (whitelist used by sort_clause()). */
    public static function sortColumns(): array
    {
        return [
            'id' => 'f.farmer_id', 'name' => 'f.last_name', 'contact' => 'f.contact_number',
            'address' => 'f.address', 'registered' => 'f.date_registered', 'farms' => 'farm_count',
        ];
    }

    /** @return array [WHERE sql, params] for the filters q and address */
    private static function filter(array $f): array
    {
        $where = [];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = "(f.first_name ILIKE :q OR f.last_name ILIKE :q OR (f.first_name || ' ' || f.last_name) ILIKE :q
                 OR f.contact_number ILIKE :q OR f.address ILIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (($f['address'] ?? '') !== '') {
            $where[] = 'f.address = :address';
            $params[':address'] = $f['address'];
        }
        return [static::whereSql($where), $params];
    }

    public static function countMatching(array $filters): int
    {
        [$where, $params] = static::filter($filters);
        return (int) static::fetchValue("SELECT COUNT(*) FROM farmer f $where", $params);
    }

    public static function search(array $filters, string $orderBy, int $limit, int $offset): array
    {
        [$where, $params] = static::filter($filters);
        return static::fetchAll(
            "SELECT f.farmer_id, f.first_name, f.last_name, f.contact_number, f.address, f.date_registered,
                    (SELECT COUNT(*) FROM farm fa WHERE fa.farmer_id = f.farmer_id) AS farm_count
               FROM farmer f $where
              ORDER BY $orderBy, f.farmer_id
              LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /** Distinct addresses for the filter dropdown. */
    public static function addresses(): array
    {
        return Database::connection()->query('SELECT DISTINCT address FROM farmer ORDER BY address')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Rows of [id, label] for a farmer dropdown. */
    public static function options(): array
    {
        return static::fetchAll("SELECT farmer_id AS id, last_name || ', ' || first_name AS label FROM farmer ORDER BY last_name, first_name");
    }

    /** One farmer with the names of the users who created / last updated it. */
    public static function detail(int $id): ?array
    {
        return static::fetchOne(
            'SELECT f.*, cu.full_name AS created_name, uu.full_name AS updated_name
               FROM farmer f
               LEFT JOIN users cu ON cu.user_id = f.created_by
               LEFT JOIN users uu ON uu.user_id = f.updated_by
              WHERE f.farmer_id = :id',
            [':id' => $id]
        );
    }
}
