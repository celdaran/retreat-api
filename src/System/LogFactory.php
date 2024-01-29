<?php namespace App\System;

class LogFactory
{

    private static ?Log $log = null;

    public static function getLogger(): Log
    {
        if (self::$log === null) {
            $log = new Log();
            $log->setLevel($_ENV['LOG_LEVEL']);
            $log->setOutput($_ENV['LOG_OUTPUT']);
            self::$log = $log;
        }
        return self::$log;
    }

}
