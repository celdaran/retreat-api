<?php namespace App\Service;

class Log
{
    private $level = 0;
    private $levels = [
        0 => 'OFF',
        1 => 'FATAL',
        2 => 'ERROR',
        3 => 'WARN',
        4 => 'INFO',
        5 => 'DEBUG',
    ];

    public function setLevel(string $level): Log
    {
        $pos = array_search($level, $this->levels);
        if ($pos === false) {
            die("Invalid level specified: $level");
        }
        $this->level = $pos;
        return $this;
    }

    public function debug(string $message)
    {
        $this->_log(5, $message);
    }

    public function info(string $message)
    {
        $this->_log(4, $message);
    }

    public function warn(string $message)
    {
        $this->_log(3, $message);
    }

    private function _log(int $level, string $message)
    {
        if ($this->level >= $level) {
            print(date('c') . ' ' . $this->levels[$level] . ': ' . $message . "\n");
        }
    }

}