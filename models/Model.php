<?php

class Model
{
    protected $db;
    protected $table;

    public function __construct()
    {
        // assert($this->table !== null); // for debugging alright, but in production better use:  if with exception.
        $this->db = Database::getDb();
    }

    public function get($id)
    {
        $query = sprintf(
            'SELECT * FROM %s WHERE id = %s',
            $this->table,
            $id
        );

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute();

        return $result->fetchArray(SQLITE3_ASSOC);
    }

    public function getAll()
    {
        $query = sprintf(
            'SELECT * FROM %s',
            $this->table
        );

        $stmt = $this->db->prepare($query);
        $result = $stmt->execute();

        $rows = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }
}
