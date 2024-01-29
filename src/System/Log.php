<?php namespace App\System;

class Log
{
    /** @var int */
    private int $level = 0;

    /** @var string */
    private string $output = 'FILE';

    /** @var array|string[] */
    private array $levels = [
        0 => 'OFF',
        1 => 'FATAL',
        2 => 'ERROR',
        3 => 'WARN',
        4 => 'INFO',
        5 => 'DEBUG',
        6 => 'TRACE',
        9 => 'ALL',
    ];

    /** @var array */
    private array $logs;

    /**
     * @param string $level
     * @return Log
     */
    public function setLevel(string $level): Log
    {
        $this->level = $this->str2int($level);
        return $this;
    }

    public function setOutput(string $output): Log
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param string $message
     */
    public function debug(string $message)
    {
        $this->_log('DEBUG', $message);
    }

    /**
     * @param string $message
     */
    public function info(string $message)
    {
        $this->_log('INFO', $message);
    }

    /**
     * @param string $message
     */
    public function warn(string $message)
    {
        $this->_log('WARN', $message);
    }

    /**
     * @param string $message
     */
    public function error(string $message)
    {
        $this->_log('ERROR', $message);
    }

    /**
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Core logging function
     *
     * @param string $level
     * @param string $message
     */
    private function _log(string $level, string $message)
    {
        // Convert string level to integer
        $level = $this->str2int($level);

        // Test if logging level is met
        if ($this->level >= $level) {

            // Generate message
            $message = sprintf("%s %s: %s\n",
                date('c'),
                $this->align($this->levels[$level]),
                $message
            );

            // Save message to object
            $this->logs[] = $message;

            // Return
            if ($this->output === 'STDOUT') {
                print($message);
            }
        }
    }

    /**
     * Convert string representation of log level to integer
     * @param string $level
     * @return int
     */
    private function str2int(string $level): int
    {
        $pos = array_search($level, $this->levels);
        if ($pos === false) {
            die("Invalid level specified: $level");
        }
        return $pos;
    }

    /**
     * Return a string left-padded with strings to match
     * the longest string in the list of defined levels
     *
     * @param string $text
     * @return string
     */
    private function align(string $text): string
    {
        $maxWidth = 0;
        foreach ($this->levels as $level) {
            if (strlen($level) > $maxWidth) {
                $maxWidth = strlen($level);
            }
        }
        return str_pad($text, $maxWidth, ' ', STR_PAD_LEFT);
    }

}
