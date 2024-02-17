<?php namespace App\System;

use Exception;
use Ifsnop\Mysqldump;

/**
 * For unit testing only!
 */
class DatabaseReset
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Create tables
     * @param bool $backup
     */
    public function reset(bool $backup = true)
    {
        if ($backup) {
          $this->_saveDatabase();
        }
        $this->_dropTables();
        $this->_createTables();
    }

    /**
     * Populate tables
     * @param bool $includeSystem
     * @param bool $includeFixtures
     * @param bool $includeProduction
     */
    public function hydrate(bool $includeSystem, bool $includeFixtures = false, bool $includeProduction = false)
    {
        $this->_hydrate($includeSystem, $includeFixtures, $includeProduction);
    }

    /**
     * Run mysqldump on database
     */
    private function _saveDatabase()
    {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s', $this->db->getHost(), $this->db->getName());
            $dump = new Mysqldump\Mysqldump($dsn, $this->db->getUser(), $this->db->getPass());
            $dump->start($this->db->getName() . '.' . date('Ymd-His') . '.sql');
        }
        catch (Exception $e) {
            echo "mysqldump error: " . $e->getMessage() . "\n";
            exit;
        }
    }

    /**
     * Drop all tables
     */
    private function _dropTables()
    {
        $sql = '
            SET FOREIGN_KEY_CHECKS = 0;
            DROP TABLE IF EXISTS scenario;
            DROP TABLE IF EXISTS account_type;
            DROP TABLE IF EXISTS income_type;
            DROP TABLE IF EXISTS expense;
            DROP TABLE IF EXISTS asset;
            DROP TABLE IF EXISTS earnings;
            DROP TABLE IF EXISTS simulation;
            SET FOREIGN_KEY_CHECKS = 0;
        ';
        $this->db->exec($sql);
    }

    /**
     * Create schema (DDL)
     */
    private function _createTables()
    {
        $sql = file_get_contents(__DIR__.'/../../db/retreat-schema.sql');
        $this->db->exec($sql);
    }

    /**
     * Add rows (DML)
     * @param bool $includeSystem
     * @param bool $includeFixtures
     * @param bool $includeProduction
     */
    private function _hydrate(bool $includeSystem, bool $includeFixtures = false, bool $includeProduction = false)
    {
        if ($includeSystem) {
            $sql = file_get_contents(__DIR__.'/../../db/retreat-schema-hydrate.sql');
            $this->db->exec($sql);
        }

        if ($includeFixtures) {
            $sql = file_get_contents(__DIR__.'/../../db/retreat-schema-hydrate.unit-tests.sql');
            $this->db->exec($sql);
        }

        if ($includeProduction) {
            $sql = file_get_contents(__DIR__.'/../../db/db-production/retreat-schema-hydrate.production.sql');
            $this->db->exec($sql);
        }
    }

}
