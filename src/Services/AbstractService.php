<?php
/**
 * @author Matt Frost<mattf@budgetdumpster.com>
 * @package BudgetDumpster
 * @subpackage Services
 * @copyright Budget Dumpster, LLC 2017
 */
namespace BudgetDumpster\Services;

use \Monolog\Logger;

abstract class AbstractService
{
    /**
     * @var Monolog\Logger
     */
    protected $logger;

    /**
     * Constructor for all internal services
     *
     * @param Monolog\Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Method for generating the logging context from an exception
     *
     * @param \Exception $exception
     * @param Array $additionalContexts
     * @param boolean $trace
     * @return Array
     */
    protected function getLoggingContext(\Exception $e, $additionalContexts = [], $trace = false)
    {
        $error_context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];

        if ($trace) {
            $error_context['trace'] = $e->getTrace();
        }

        foreach ($additionalContexts as $context) {
            $error_context = array_merge($error_context, $context);
        }

        return $error_context;
    }
}

