<?php

class BaseDao
{
    protected $conn;
    protected $table_name;

    // Constructor
    public function __construct($table_name)
    {
        $this->table_name = $table_name;
        $host = getenv('DB_HOST');
        $user = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');
        $port = getenv('DB_PORT');
        $dbname = getenv('DB_NAME');

        // Create PostgreSQL connection
        $this->conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    }

    // Method to read all objects from the database
    public function get_all()
    {
        $result = pg_query($this->conn, "SELECT * FROM " . $this->table_name);
        return pg_fetch_all($result);
    }

    // Method to read object from database by ID
    public function get_by_id($id)
    {
        $result = pg_query_params($this->conn, "SELECT * FROM " . $this->table_name . " WHERE id=$1", array($id));
        return pg_fetch_assoc($result);
    }

    // Add record to database
    public function add($entity)
    {
        $columns = implode(", ", array_keys($entity));
        $placeholders = implode(", ", array_fill(1, count($entity), '$' . (count($entity) + 1)));

        $query = "INSERT INTO " . $this->table_name . " ($columns) VALUES ($placeholders) RETURNING id";

        $result = pg_query_params($this->conn, $query, array_values($entity));
        $row = pg_fetch_assoc($result);
        $entity['id'] = $row['id'];

        return $entity;
    }


    // Method to update record in the database
    public function update($id, $entity, $id_column = "id")
    {
        $set_clause = "";
        $values = array_values($entity);

        foreach ($entity as $name => $value) {
            $set_clause .= "$name = $" . (count($values) + 1) . ", ";
            $values[] = $value;
        }

        $set_clause = rtrim($set_clause, ", ");
        $values[] = $id;

        $query = "UPDATE " . $this->table_name . " SET $set_clause WHERE $id_column = $" . (count($values));

        $result = pg_query_params($this->conn, $query, $values);
        return $entity;
    }

    // Delete record from the database
    public function delete($id)
    {
        $result = pg_query_params($this->conn, "DELETE FROM " . $this->table_name . " WHERE id = $1", array($id));
    }

    protected function query($query, $params = [])
    {
        $result = pg_query_params($this->conn, $query, $params);
        return pg_fetch_all($result);
    }

    protected function query_unique($query, $params)
    {
        $results = $this->query($query, $params);
        return reset($results);
    }
}

?>
