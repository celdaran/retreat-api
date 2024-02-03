<?php namespace App\System;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private ?PDO $dbh;
    private string $lastInsertId;
    private array $lastError;
    private Log $log;

    private string $host;
    private string $name;
    private string $user;
    private string $pass;

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
                $this->host = $host;
                $this->name = $database;
                $this->user = $username;
                $this->pass = $password;
            }
        } catch (PDOException $e) {
            $this->log->error("Error connecting to database: " . $e->getMessage() . "\n");
        }
    }

    public function select(string $query, array $parameters = []): array
    {
        $sth = $this->exec($query, $parameters);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exec(string $query, array $parameters = []): PDOStatement
    {
        $sth = $this->dbh->prepare($query);
        $sth->execute($parameters);
        $this->lastInsertId = $this->dbh->lastInsertId();
        $this->lastError = $sth->errorInfo();
        return $sth;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPass(): string
    {
        return $this->pass;
    }

    public function lastInsertId(): int
    {
        return $this->lastInsertId;
    }

    public function lastError(): array
    {
        return $this->lastError;
    }

}
