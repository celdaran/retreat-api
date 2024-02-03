<?php namespace App\System;


use App\System\Database;

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

    public function reset()
    {
        $this->_dropTables();
        $this->_createTables();
        $this->_hydrate(true);
    }

    private function _dropTables()
    {
        $sql = '
            SET FOREIGN_KEY_CHECKS = 0;
            DROP TABLE IF EXISTS scenario;
            DROP TABLE IF EXISTS account_type;
            DROP TABLE IF EXISTS expense;
            DROP TABLE IF EXISTS asset;
            DROP TABLE IF EXISTS earnings;
            DROP TABLE IF EXISTS simulation;
            SET FOREIGN_KEY_CHECKS = 0;
        ';
        $this->db->exec($sql);
    }

    private function _createTables()
    {
        $sql = file_get_contents(__DIR__.'/../../db/retreat-schema.sql');
        $this->db->exec($sql);
    }

    private function _hydrate(bool $includeFixtures = false)
    {
        $sql = file_get_contents(__DIR__.'/../../db/retreat-schema-hydrate.sql');
        $this->db->exec($sql);

        if ($includeFixtures) {
            $sql = file_get_contents(__DIR__.'/../../db/retreat-schema-hydrate.unit-tests.sql');
            $this->db->exec($sql);
        }
    }

}
