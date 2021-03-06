<?php
/**
 * Packfire Options
 * By Sam-Mauris Yong
 * 
 * Released open source under New BSD 3-Clause License.
 * Copyright (c) 2012, Sam-Mauris Yong Shan Xian <sam@mauris.sg>
 * All rights reserved.
 */

namespace Packfire\Options;

/**
 * OptionSet class
 *
 * Parses command line arguments
 *
 * @author Sam-Mauris Yong / mauris@hotmail.sg
 * @copyright Copyright (c) 2010-2012, Sam-Mauris Yong
 * @license http://www.opensource.org/licenses/bsd-license New BSD License
 * @package Packfire\Options
 * @since 1.0.0
 */
class OptionSet implements IOptionSet
{
    
    /**
     * The collection of options
     * @var array
     * @since 1.0.0
     */
    private $options = array();
    
    /**
     * Collection of index-based options
     * @var array
     * @since 1.0.0
     */
    private $indexOptions = array();
    
    /**
     * Add a new option
     * @param string $name Name of the option
     * @param Closure|callback $callback The callback to handle values for the option
     * @param string $help (optional) The help text for the option
     * @since 1.0.0
     */
    public function add($name, $callback, $help = null)
    {
        $this->options[] = new Option($name, $callback, $help);
    }
    
    /**
     * Add a new index-based option
     * @param integer $index The index of the 
     * @param Closure|callback $callback The callback to handle values for the option
     * @param string $help (optional) The help text for the option
     * @since 1.0.0
     */
    public function addIndex($index, $callback, $help = null)
    {
        $this->indexOptions[] = new Option($index, $callback, $help);
    }
    
    /**
     * Parse the arguments
     * @param array|\Packfire\Collection\ArrayList $args The array of arguments to parse
     * @since 1.0.0
     */
    public function parse($args)
    {
        $argLength = count($args);
        foreach ($this->indexOptions as $option) {
            /* @var $option Packfire\Command\Option */
            $index = $option->index();
            if ($index < 0) { // if index is negative, then we take from the back
                $index = $argLength + $index;
            }
            if (isset($args[$index])) {
                $value = $args[$index];
                $option->execute($value);
            }
        }
        $iterator = new \ArrayIterator($args);
        while ($iterator->valid()) {
            $argValue = $iterator->current();
            $key = null;
            $valuelessOption = false;
            $firstChar = substr($argValue, 0, 1);
            if ($firstChar == '/' || $firstChar == '-') {
                if (substr($argValue, 0, 2) == '--') {
                    $key = substr($argValue, 2);
                } else {
                    $key = substr($argValue, 1);
                    if ($firstChar == '-' && strlen($key) > 1) {
                        $valuelessOption = true;
                        $key = str_split($key);
                    }
                }
            }
            if ($key) {
                $keys = (array)$key;
                foreach ($keys as $key) {
                    $value = null;
                    if (!$valuelessOption && false !== ($kvPos = strpos($key, '='))) {
                        $value = substr($key, $kvPos + 1);
                        $key = substr($key, 0, $kvPos);
                    }
                    foreach ($this->options as $option) {
                        /* @var $option \Packfire\Options\Option */
                        if ($option->matchName($key)) {
                            if (!$valuelessOption && $value === null && $option->hasValue()) {
                                $iterator->next();
                                $value = $iterator->current();
                            }
                            $option->execute($value);
                            break;
                        }
                    }
                }
            }
            $iterator->next();
        }
    }
    
    /**
     * Build the help text for the option set
     * @return string Returns the string containing compiled help text
     * @since 1.0.1
     */
    public function help()
    {
        $buffer = '';
        foreach ($this->options as $option) {
            /* @var $option \Packfire\Options\Option */
            $buffer .= $option->formatNames(false);
            $buffer .= '  ' . ($option->required() ? '(required) ' : '')
                .  $option->help() . "\n";
        }
        return $buffer;
    }
}
