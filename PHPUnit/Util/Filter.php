<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2001-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit
 * @subpackage Util
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2001-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 2.0.0
 */

/**
 * Utility class for code filtering.
 *
 * @package    PHPUnit
 * @subpackage Util
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2001-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 2.0.0
 */
class PHPUnit_Util_Filter
{
    private static $filteredClassNamePrefixes = array(
        'File_Iterator',
        'PHP_CodeCoverage',
        'PHP_Invoker',
        'PHP_Timer',
        'PHP_Token',
        'PHPUnit_',
        'SebastianBergmann\Diff',
        'SebastianBergmann\Exporter',
        'SebastianBergmann\Version',
        'Text_Template'
    );

    /**
     * Filters stack frames from PHPUnit classes.
     *
     * @param  Exception $e
     * @param  boolean   $asString
     * @return string
     */
    public static function getFilteredStacktrace(Exception $e, $asString = TRUE)
    {
        if ($asString === TRUE) {
            $filteredStacktrace = '';
        } else {
            $filteredStacktrace = array();
        }

        if ($e instanceof PHPUnit_Framework_SyntheticError) {
            $trace = $e->getSyntheticTrace();
        }

        else if ($e->getPrevious()) {
            $trace = $e->getPrevious()->getTrace();
        }

        else {
            $trace = $e->getTrace();
        }

        if (!defined('PHPUNIT_TESTSUITE')) {
            self::removeFramesBeforeTestMethod($trace);
        }

        self::fixSourceLocation($trace);

        foreach ($trace as $frame) {
            if (!isset($frame['file']) ||
                (!defined('PHPUNIT_TESTSUITE') && isset($frame['class']) && self::isFiltered($frame['class']))) {
                continue;
            }

            if ($asString === TRUE) {
                $filteredStacktrace .= sprintf(
                    "%s:%s\n",

                    $frame['file'],
                    isset($frame['line']) ? $frame['line'] : '?'
                );
            } else {
                $filteredStacktrace[] = $frame;
            }
        }

        return $filteredStacktrace;
    }

    /**
     * @param array $trace
     */
    private static function removeFramesBeforeTestMethod(array &$trace)
    {
        $done = FALSE;

        while (!$done && !empty($trace)) {
            $frame = array_pop($trace);

            if ($frame['class'] == 'ReflectionMethod' &&
                $frame['function'] == 'invokeArgs' &&
                substr($frame['file'], -12) == 'TestCase.php') {
                $done = TRUE;
            }
        }
    }

    /**
     * @param array $trace
     */
    private static function fixSourceLocation(array &$trace)
    {
        for ($frame = count($trace) - 1; $frame > 0; $frame--) {
            if (isset($trace[$frame - 1]['file'])) {
                $trace[$frame]['file'] = $trace[$frame - 1]['file'];
            }

            if (isset($trace[$frame - 1]['line'])) {
                $trace[$frame]['line'] = $trace[$frame - 1]['line'];
            }
        }

        unset($trace[0]);
    }

    /**
     * @param  string $className
     * @return boolean
     * @since  Class available since Release 3.8.0
     */
    private static function isFiltered($className)
    {
        foreach (self::$filteredClassNamePrefixes as $prefix) {
            if (strpos($className, $prefix) === 0) {
                return TRUE;
            }
        }

        return FALSE;
    }
}
