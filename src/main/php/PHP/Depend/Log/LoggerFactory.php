<?php
/**
 * This file is part of PDepend.
 *
 * PHP Version 5
 *
 * Copyright (c) 2008-2012, Manuel Pichler <mapi@pdepend.org>.
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
 *   * Neither the name of Manuel Pichler nor the names of his
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
 * @category  QualityAssurance
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id$
 * @link      http://pdepend.org/
 */

namespace PHP\Depend\Log;

/**
 * This factory creates singleton instances of available loggers.
 *
 * The identifiers used for loggers follow a simple convention. Every upper case
 * word in the class file name and the logger directory is separated by a hyphen.
 * Only the last word of an identifier is used for the class file name, all
 * other words are used for the directory name.
 *
 * <code>
 *   --my-custom-log-xml
 * </code>
 *
 * Refers to the following file: <b>PHP/Depend/Log/MyCustomLog/Xml.php</b>, but
 * you can not reference a file named <b>PHP/Depend/Log/MyCustom/LogXml.php</b>.
 *
 * @category  QualityAssurance
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://pdepend.org/
 */
class LoggerFactory
{
    /**
     * Set of created logger instances.
     *
     * @var \PHP\Depend\Log\Report[]
     */
    protected $instances = array();

    /**
     * Creates a new report or returns an existing instance for the given
     * <b>$identifier</b>.
     *
     * @param string $identifier
     * @param string $fileName
     * @return \PHP\Depend\Log\Report
     */
    public function createReport($identifier, $fileName)
    {
        if (!isset($this->instances[$identifier])) {

            $words = array_map('ucfirst', explode('-', $identifier));

            // By definition the logger class name must be a single word.
            // Everything else is part of the package name.
            $class   = array_pop($words);
            $package = implode('', $words);

            $className = sprintf('\\PHP\\Depend\\Log\\%s\\%s', $package, $class);
            $classFile = sprintf('PHP/Depend/Log/%s/%s.php', $package, $class);

            if (class_exists($className) === false) {

                if (($handle = @fopen($classFile, 'r', true)) === false) {
                    throw new \RuntimeException(
                        "Unknown logger class '{$className}'."
                    );
                }

                fclose($handle);
                include $classFile;
            }

            $logger = new $className();
            if ($logger instanceof FileAware) {

                $logger->setLogFile($fileName);
            }

            $this->instances[$identifier] = $logger;
        }
        return $this->instances[$identifier];
    }
}
