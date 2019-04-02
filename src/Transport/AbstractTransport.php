<?php

namespace LogEngine\Transport;


use LogEngine\Contracts\TransportInterface;
use LogEngine\Exceptions\LogEngineException;

abstract class AbstractTransport implements TransportInterface
{
    /**
     * Able to use the library in debug mode.
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Location of package's log file used for debug.
     *
     * @var string
     */
    private $debugLogPath;

    /**
     * AbstractTransport constructor.
     */
    public function __construct()
    {
        $ds = DIRECTORY_SEPARATOR;
        $this->debugLogPath = realpath(dirname(__FILE__) . "$ds..$ds..") . $ds . 'debug/log.log';
    }

    /**
     * List of available transport's options with validation regex.
     *
     * ['param-name' => 'regex']
     *
     * @return mixed
     */
    protected abstract function getAllowedOptions();

    /**
     * Verify if given options match constraints.
     *
     * @param $options
     * @throws LogEngineException
     */
    protected function extractOptions($options)
    {
        foreach ($this->getAllowedOptions() as $name => $regex) {
            if (isset($options[$name])) {
                $value = $options[$name];
                if (preg_match($regex, $value)) {
                    $this->$name = $value;
                } else {
                    throw new LogEngineException("Option '$name' has invalid value");
                }
            }
        }
    }

    /**
     * Register an error for package's debug.
     *
     * @param string $message
     */
    protected function logError($message)
    {
        $this->log($message, func_get_args(), false);
    }

    /**
     * Register a message for package's debug.
     *
     * @param string $message
     */
    protected function logDebug($message)
    {
        if (!$this->debug) {
            return;
        }
        $this->log($message, func_get_args(), true);
    }

    /**
     * Write a string inside package's log file for debug purpose.
     *
     * @param $message
     * @param $args
     * @param bool $success
     */
    private function log($message, $args, $success = true)
    {
        $replacements = array_slice($args, 1);
        $prefix = $success ? 'Log' : 'Error';
        $template = date('Y-m-d H:i:s')." - [$prefix] [".get_class($this)."] $message ";

        $formatted = preg_replace('/\r\n/', '', vsprintf($template, $replacements));

        // first option - write to local file if possible
        // this can be not available because of file permissions
        @file_put_contents($this->debugLogPath, "$formatted\n\n", FILE_APPEND);

        if (!$success) {
            // second option - send to default PHP error log
            error_log($formatted);
        }
    }
}