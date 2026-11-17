<?php

abstract class Model
{
    /** Table name, primary key column and the name shown in messages (set by each subclass). */
    protected static string $table = '';
    protected static string $primaryKey = '';
    protected static string $title = '';

    protected ?int $id = null;
    /** Form fields of this record: column => value (strings, as typed in the form). */
    protected array $attributes = [];
    /** Validation messages: field => message. */
    protected array $errors = [];

    /** @param array $input values for the fields (for example $_POST or a database row) */
    public function __construct(array $input = [])
    {
        $this->attributes = static::defaults();
        $this->fill($input);
    }

    // ------------------------------------------------------------ what each subclass supplies

    /** The fields of the form with their starting values. */
    abstract protected static function defaults(): array;

    /** Validation rules. Call $this->addError('field', 'message') for every problem. */
    abstract protected function rules(): void;

    /** Column => typed value pairs written to the database (called after validation passed). */
    abstract protected function columnValues(): array;

    /** Short text for the audit log, for example "farmer Juan Dela Cruz". */
    abstract protected function auditLabel(): string;

    /** SQL (with :id) that counts rows depending on this one. Empty when nothing depends on it. */
    protected static function dependentsSql(): string
    {
        return '';
    }

    // ------------------------------------------------------------ fields

    /** Copies the known fields from $input (unknown keys are ignored) and trims the values. */
    public function fill(array $input): static
    {
        foreach ($this->attributes as $field => $_) {
            if (array_key_exists($field, $input)) {
                $value = $input[$field];
                $this->attributes[$field] = is_scalar($value) ? trim((string) $value) : '';
            }
        }
        return $this;
    }

    /** Fills every field from a submitted form; fields missing from the form become empty. */
    public function fillFromForm(array $post): static
    {
        foreach ($this->attributes as $field => $_) {
            $value = $post[$field] ?? '';
            $this->attributes[$field] = is_string($value) ? trim($value) : '';
        }
        return $this;
    }

    public function __get(string $field)
    {
        return $this->attributes[$field] ?? null;
    }

    public function __set(string $field, $value): void
    {
        if (array_key_exists($field, $this->attributes)) {
            $this->attributes[$field] = is_scalar($value) ? trim((string) $value) : '';
        }
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function title(): string
    {
        return static::$title;
    }

    // ------------------------------------------------------------ validation

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    protected function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /** Runs the rules and returns the errors (empty array = valid). */
    public function validate(): array
    {
        $this->errors = [];
        $this->rules();
        return $this->errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    // ------------------------------------------------------------ reading

    /** Loads one record, or null when it does not exist. */
    public static function find(int $id): ?static
    {
        $st = Database::connection()->prepare(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id'
        );
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $record = new static();
        $record->fill($row);
        $record->id = (int) $row[static::$primaryKey];
        return $record;
    }

    public static function exists(int $id): bool
    {
        $st = Database::connection()->prepare(
            'SELECT 1 FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id'
        );
        $st->execute([':id' => $id]);
        return (bool) $st->fetchColumn();
    }

    public static function notFoundMessage(): string
    {
        $title = static::$title;
        return str_ends_with(strtolower($title), 'record') ? "$title was not found." : "$title record was not found.";
    }

    // ------------------------------------------------------------ saving

    /**
     * INSERT (new record) or UPDATE (existing record). Sets created_by / updated_by and writes
     * the audit log. Call validate() first. Throws PDOException when the database refuses.
     * @return int the record id
     */
    public function save(?int $userId = null): int
    {
        $userId ??= current_user()['user_id'] ?? null;
        $values = $this->columnValues();
        $columns = array_keys($values);
        $params = [];
        foreach ($values as $column => $value) {
            $params[":$column"] = $value;
        }
        $pdo = Database::connection();

        if ($this->isNew()) {
            $sql = sprintf(
                'INSERT INTO %s (%s, created_by, updated_by) VALUES (%s, :created_by, :updated_by) RETURNING %s',
                static::$table,
                implode(', ', $columns),
                implode(', ', array_map(fn($c) => ":$c", $columns)),
                static::$primaryKey
            );
            $st = $pdo->prepare($sql);
            $st->execute($params + [':created_by' => $userId, ':updated_by' => $userId]);
            $this->id = (int) $st->fetchColumn();
            AuditLog::record('CREATE', static::$table, $this->id, 'Added ' . $this->auditLabel());
        } else {
            $sql = sprintf(
                'UPDATE %s SET %s, updated_by = :updated_by WHERE %s = :record_id',
                static::$table,
                implode(', ', array_map(fn($c) => "$c = :$c", $columns)),
                static::$primaryKey
            );
            $pdo->prepare($sql)->execute($params + [':updated_by' => $userId, ':record_id' => $this->id]);
            AuditLog::record('UPDATE', static::$table, $this->id, 'Updated ' . $this->auditLabel());
        }
        return $this->id;
    }

    /** Message shown after a successful save. */
    public function savedMessage(bool $wasNew): string
    {
        return static::$title . ($wasNew ? ' added successfully.' : ' updated successfully.');
    }

    // ------------------------------------------------------------ deleting

    /**
     * Deletes a record unless other records still use it (referential integrity).
     * @return array [flash type, message]
     */
    public static function destroy(int $id): array
    {
        $title = static::$title;
        if ($id <= 0 || !static::exists($id)) {
            return ['danger', static::notFoundMessage()];
        }
        $dependents = static::dependentsSql();
        if ($dependents !== '') {
            $st = Database::connection()->prepare($dependents);
            $st->execute([':id' => $id]);
            $n = (int) $st->fetchColumn();
            if ($n > 0) {
                return ['danger', "Cannot delete this $title: it is used by $n related record(s). Remove or reassign them first."];
            }
        }
        try {
            $st = Database::connection()->prepare(
                'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = :id'
            );
            $st->execute([':id' => $id]);
            AuditLog::record('DELETE', static::$table, $id, "Deleted $title #$id");
            return ['success', "$title deleted successfully."];
        } catch (PDOException $e) {
            return ['danger', friendly_db_error($e)];
        }
    }

    // ------------------------------------------------------------ helpers for the subclasses

    /** Joins WHERE conditions: returns [' WHERE a AND b' or '', params]. */
    protected static function whereSql(array $conditions): string
    {
        return $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }

    /** Runs a SELECT and returns all rows. */
    protected static function fetchAll(string $sql, array $params = []): array
    {
        $st = Database::connection()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    protected static function fetchOne(string $sql, array $params = []): ?array
    {
        $st = Database::connection()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row ?: null;
    }

    protected static function fetchValue(string $sql, array $params = [])
    {
        $st = Database::connection()->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }
}
