<?php
class Database{
    public mysqli $connection;
    public function __construct(string $host,string $username,string $password,string $database) {
        $this->connection = new mysqli($host,$username,$password,$database);
    }
    public function transaction($handler){
        $this->connection->begin_transaction();
        try {
            $handler($this->connection);
            $this->connection->commit();
        } catch (\Throwable $th) {
            $this->connection->rollback();
            throw $th;
        }
    }
    public function query(string $query,string $type,array $variable,&$affected_row = 0):mysqli_result|false{
        $statement = $this->connection->prepare($query);
        $statement->bind_param($type,...$variable);
        $statement->execute();
        $affected_row = $statement->affected_rows;
        return $statement->get_result();
    }
}
?>