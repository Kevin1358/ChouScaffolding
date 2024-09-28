<?php
class Database{
    public PDO $connection;
    public function __construct(PDO $connection) {
        $this->connection = $connection;
    }
    public function transaction($handler){
        $this->connection->beginTransaction();
        try {
            $handler($this->connection);
            $this->connection->commit();
        } catch (\Throwable $th) {
            $this->connection->rollback();
            throw $th;
        }
    }
    public function query(string $query,array $variable,&$affected_row = 0):PDOStatement{
        $statement = $this->connection->prepare($query);
        $statement->execute($variable);
        $affected_row = $statement->rowCount();
        return $statement;
    }
}
?>