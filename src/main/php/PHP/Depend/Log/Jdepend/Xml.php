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

namespace PHP\Depend\Log\Jdepend;

use \PHP\Depend\AST\ASTClass;
use \PHP\Depend\AST\ASTInterface;
use \PHP\Depend\Log\CodeAware;
use \PHP\Depend\Log\FileAware;
use \PHP\Depend\Log\NoLogOutputException;
use \PHP\Depend\Metrics\Analyzer;

/**
 * Generates an xml document with the aggregated metrics. The format is borrowed
 * from <a href="http://clarkware.com/software/JDepend.html">JDepend</a>.
 *
 * @category  QualityAssurance
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://pdepend.org/
 */
class Xml implements CodeAware, FileAware
{
    /**
     * The type of this class.
     */
    const CLAZZ = __CLASS__;

    /**
     * The output log file.
     *
     * @var string
     */
    private $logFile = null;

    /**
     * The raw {@link \PHP_Depend_AST_Package} instances.
     *
     * @var \PHP_Depend_AST_NodeIterator
     */
    protected $code = null;

    /**
     * Set of all analyzed files.
     *
     * @var PHP_Depend_AST_File[] $fileSet
     */
    protected $fileSet = array();

    /**
     * List of all generated project metrics.
     *
     * @var array(string=>mixed) $projectMetrics
     */
    protected $projectMetrics = array();

    /**
     * List of all collected node metrics.
     *
     * @var array(string=>array) $nodeMetrics
     */
    protected $nodeMetrics = array();

    /**
     * The depedency result set.
     *
     * @var \PHP\Depend\Metrics\Dependency\Analyzer
     */
    protected $analyzer = null;

    /**
     * The Packages dom element.
     *
     * @var \DOMElement
     */
    protected $packages = null;

    /**
     * The Cycles dom element.
     *
     * @var DOMElement $cycles
     */
    protected $cycles = null;

    /**
     * The concrete classes element for the current package.
     *
     * @var DOMElement $concreteClasses
     */
    protected $concreteClasses = null;

    /**
     * The abstract classes element for the current package.
     *
     * @var DOMElement $abstractClasses
     */
    protected $abstractClasses = null;

    /**
     * Sets the output log file.
     *
     * @param string $logFile The output log file.
     * @return void
     */
    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Returns an <b>array</b> with accepted analyzer types. These types can be
     * concrete analyzer classes or one of the descriptive analyzer interfaces.
     *
     * @return array(string)
     */
    public function getAcceptedAnalyzers()
    {
        return array(\PHP\Depend\Metrics\Dependency\Analyzer::CLAZZ);
    }

    /**
     * Adds an analyzer to log. If this logger accepts the given analyzer it
     * with return <b>true</b>, otherwise the return value is <b>false</b>.
     *
     * @param \PHP\Depend\Metrics\Analyzer $analyzer
     * @return boolean
     */
    public function log(Analyzer $analyzer)
    {
        if ($analyzer instanceof \PHP\Depend\Metrics\Dependency\Analyzer) {
            $this->analyzer = $analyzer;

            return true;
        }
        return false;
    }

    /**
     * Closes the logger process and writes the output file.
     *
     * @return void
     * @throws \PHP\Depend\Log\NoLogOutputException If the no log target exists.
     */
    public function close()
    {
        // Check for configured output
        if ($this->logFile === null) {
            throw new NoLogOutputException($this);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        $dom->formatOutput = true;

        $jdepend = $dom->createElement('PDepend');

        $this->packages = $jdepend->appendChild($dom->createElement('Packages'));
        $this->cycles   = $jdepend->appendChild($dom->createElement('Cycles'));

        foreach ($this->code as $node) {
            $node->accept($this);
        }

        $dom->appendChild($jdepend);
        $dom->save($this->logFile);
    }

    /**
     * Visits a class node.
     *
     * @param \PHP\Depend\AST\ASTClass $class
     * @return void
     */
    public function visitClass(ASTClass $class)
    {
        if (!$class->isUserDefined()) {
            return;
        }

        $doc = $this->packages->ownerDocument;

        $classXml = $doc->createElement('Class');
        $classXml->setAttribute('sourceFile', (string)$class->getSourceFile());
        $classXml->appendChild($doc->createTextNode($class->getName()));

        if ($class->isAbstract()) {
            $this->abstractClasses->appendChild($classXml);
        } else {
            $this->concreteClasses->appendChild($classXml);
        }
    }

    /**
     * Visits a code interface object.
     *
     * @param \PHP\Depend\AST\ASTInterface $interface
     * @return void
     */
    public function visitInterface(ASTInterface $interface)
    {
        if (!$interface->isUserDefined()) {
            return;
        }

        $doc = $this->abstractClasses->ownerDocument;

        $classXml = $doc->createElement('Class');
        $classXml->setAttribute('sourceFile', (string)$interface->getSourceFile());
        $classXml->appendChild($doc->createTextNode($interface->getName()));

        $this->abstractClasses->appendChild($classXml);
    }

    /**
     * Visits a package node.
     *
     * @param PHP_Depend_AST_Package $package
     * @return void
     */
    public function visitPackage(\PHP_Depend_AST_Package $package)
    {
        if (!$package->isUserDefined()) {
            return;
        }

        $stats = $this->analyzer->getStats($package);
        if (count($stats) === 0) {
            return;
        }

        $doc = $this->packages->ownerDocument;

        $this->concreteClasses = $doc->createElement('ConcreteClasses');
        $this->abstractClasses = $doc->createElement('AbstractClasses');

        $packageXml = $doc->createElement('Package');
        $packageXml->setAttribute('name', $package->getName());

        $statsXml = $doc->createElement('Stats');
        $statsXml->appendChild($doc->createElement('TotalClasses'))
            ->appendChild($doc->createTextNode($stats['tc']));
        $statsXml->appendChild($doc->createElement('ConcreteClasses'))
            ->appendChild($doc->createTextNode($stats['cc']));
        $statsXml->appendChild($doc->createElement('AbstractClasses'))
            ->appendChild($doc->createTextNode($stats['ac']));
        $statsXml->appendChild($doc->createElement('Ca'))
            ->appendChild($doc->createTextNode($stats['ca']));
        $statsXml->appendChild($doc->createElement('Ce'))
            ->appendChild($doc->createTextNode($stats['ce']));
        $statsXml->appendChild($doc->createElement('A'))
            ->appendChild($doc->createTextNode($stats['a']));
        $statsXml->appendChild($doc->createElement('I'))
            ->appendChild($doc->createTextNode($stats['i']));
        $statsXml->appendChild($doc->createElement('D'))
            ->appendChild($doc->createTextNode($stats['d']));

        $dependsUpon = $doc->createElement('DependsUpon');
        foreach ($this->analyzer->getEfferents($package) as $efferent) {
            $efferentXml = $doc->createElement('Package');
            $efferentXml->appendChild($doc->createTextNode($efferent->getName()));

            $dependsUpon->appendChild($efferentXml);
        }

        $usedBy = $doc->createElement('UsedBy');
        foreach ($this->analyzer->getAfferents($package) as $afferent) {
            $afferentXml = $doc->createElement('Package');
            $afferentXml->appendChild($doc->createTextNode($afferent->getName()));

            $usedBy->appendChild($afferentXml);
        }

        $packageXml->appendChild($statsXml);
        $packageXml->appendChild($this->concreteClasses);
        $packageXml->appendChild($this->abstractClasses);
        $packageXml->appendChild($dependsUpon);
        $packageXml->appendChild($usedBy);

        if (($cycles = $this->analyzer->getCycle($package)) !== null) {
            $cycleXml = $doc->createElement('Package');
            $cycleXml->setAttribute('Name', $package->getName());

            foreach ($cycles as $cycle) {
                $cycleXml->appendChild($doc->createElement('Package'))
                    ->appendChild($doc->createTextNode($cycle->getName()));
            }

            $this->cycles->appendChild($cycleXml);
        }

        foreach ($package->getTypes() as $type) {
            $type->accept($this);
        }

        if ($this->concreteClasses->firstChild === null
            && $this->abstractClasses->firstChild === null
        ) {
            return;
        }

        $this->packages->appendChild($packageXml);
    }
}
