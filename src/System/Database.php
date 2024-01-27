<?php namespace App\System;

use PDO;
use PDOException;

class Database
{
    private ?PDO $dbh;
    private string $lastInsertId;
    private array $lastError;
    private Log $log;

    public function __construct(Log $log)
    {
        $this->dbh = null;
        $this->lastInsertId = -1;
        $this->lastError = [];
        $this->log = $log;
    }

    public function connect(string $host, string $username, ?string $password, string $database)
    {
        try {
            if ($this->dbh === null) {
                $this->dbh = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            }
        } catch (PDOException $e) {
            $this->log->error("Error connecting to database: " . $e->getMessage() . "\n");
        }
    }

    public function select(string $query, array $parameters = [])
    {
        $sth = $this->exec($query, $parameters);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exec(string $query, array $parameters = [])
    {
        $sth = $this->dbh->prepare($query);
        $sth->execute($parameters);
        $this->lastInsertId = $this->dbh->lastInsertId();
        $this->lastError = $sth->errorInfo();
        return $sth;
    }

    public function lastInsertId()
    {
        return $this->lastInsertId;
    }

    public function lastError()
    {
        return $this->lastError;
    }

}
