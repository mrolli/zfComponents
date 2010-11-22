<?php

namespace Core\Controller\Plugin\Debug\Plugin;

/**
 * ZFDebug bar plugin to gather queries issed by doctrine
 * during current request.
 * 
 * This plugin is compatible with Doctrine2 only and requires
 * the usage of the following components:
 * - Core\Application\Container\DoctrineContainer
 * - Zend_Application / Zend_Application_Bootstrap
 *
 * @author mrolli
 */
class Doctrine extends \ZFDebug_Controller_Plugin_Debug_Plugin implements \ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{

    protected $loggers = null;

    /**
     * Plugin identifier name
     * 
     * @var string 
     */
    protected $identifier = 'doctrine';

    public function __construct(array $options=array())
    {
        /**
         * Does nothing at the moment because bootstrap is not
         * yet availabe from front controller
         */
    }

    protected function setup()
    {
        if (\is_array($this->loggers)) {
            return;
        }

        $this->loggers = array();
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');
        if ($bootstrap->hasResource('doctrine')) {
            $doctrine = $bootstrap->getResource('doctrine');
            $conNames = $doctrine->getConnectionNames();
            foreach ($conNames as $name) {
                $this->loggers[$name] = $doctrine->getConnection($name)
                                                 ->getConfiguration()
                                                 ->getSQLLogger();
            }
        }
    }

    /**
     * Returns the content for the tab
     *
     * @return string
     */
    public function getTab()
    {
        $this->setup();

        $totalQueries = 0;
        $totalTime = 0;

        foreach ($this->loggers as $logger) {
            if (is_null($logger) || !isset($logger->queries)) {
                continue;
            }

            // total query count
            $totalQueries+= count($logger->queries);

            // total query time
            foreach ($logger->queries as $query) {
                $totalTime+= $query['executionMS'] * 1000;
            }
        }

        return sprintf('%u in %0.2f ms', $totalQueries, $totalTime);
    }

    /**
     * Returns the content for the panel
     *
     * @return string
     */
    public function getPanel()
    {
        $this->setup();

        $output = '';
        foreach ($this->loggers as $name => $logger) {
            $output = '<h2>Connection "' . \htmlspecialchars($name) . '"</h2>';



            // output logger data
            if (is_null($logger) || !isset($logger->queries)) {
                $output.= '<p>No SQL-Logger available.</p>';
            } else {
                $output.= $this->getOutputForLogger($logger, $name);
            }
        }
        return $output;
    }

    /**
     * Returns the plugins identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns the base64 encoded icon
     *
     * @return string
     */
    public function getIconData()
    {
        return 'data:image/png;base64,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';
    }

    /**
     * Returns the content for one logger
     *
     * @return string
     */
    protected function getOutputForLogger(\Doctrine\DBAL\Logging\SQLLogger $logger)
    {
        if (0 == count($logger->queries)) {
            return '<p>Connection has not been used.</p>';
        }

        $items = array();
        foreach ($logger->queries as $query) {
            $items[] = \sprintf('<li><b>[%0.2f ms]</b> %s;</li>',
                    $query['executionMS'] * 1000,
                    \htmlspecialchars($this->prepareSql($query['sql'], $query['params']))
            );
        }
        return '<ol>' . \implode('', $items) . '</ol>';
    }

    protected function prepareSql($sql, $params)
    {
        foreach ($params as $param) {
            $replacement = '';
            if (is_int($param) || is_float($param) {
                $replacement = $param;
            } else if (is_string($param)) {
                $replacement = '"' . $param . '"';
            } else if (is_bool($param)) {
                $replacement = $param ? 1 : 0;
            } else if (is_object($param)) {
                if ($param instanceof DateTime) {
                    $replacement = $param->format('Y-m-d');
                }
            }
            $sql = substr_replace($sql, $replacement, strpos($sql, '?'), 1);
        }
        return $sql;
    }

}
