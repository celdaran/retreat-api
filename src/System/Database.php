<?php namespace App\System;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private PDO $dbh;
    private string $lastInsertId;
    private array $lastError;

    private string $host;
    private string $name;
    private string $user;
    private string $pass;

    public function __construct(Log $log, string $dbHost, string $dbName, string $dbUser, string $dbPass)
    {
        $this->lastInsertId = -1;
        $this->lastError = [];

        try {
            $this->dbh = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
            $this->host = $dbHost;
            $this->name = $dbName;
            $this->user = $dbUser;
            $this->pass = $dbPass;
        } catch (PDOException $e) {
            $log->error("Error connecting to database: " . $e->getMessage() . "\n");
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
