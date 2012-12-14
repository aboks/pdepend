<?php
/**
 * This file is part of PHP_Depend.
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

use \PHP\Depend\AbstractTest;
use \PHP\Depend\Log\DummyAnalyzer;

/**
 * Test case for the jdepend xml logger.
 *
 * @category  QualityAssurance
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://pdepend.org/
 *
 * @covers \PHP\Depend\Log\Jdepend\Xml
 * @group  pdepend
 * @group  pdepend::log
 * @group  pdepend::log::jdepend
 * @group  unittest
 */
class XmlTest extends AbstractTest
{
    /**
     * Test code structure.
     *
     * @var PHP_Depend_Code_NodeIterator
     */
    protected $packages;

    /**
     * Test dependency analyzer.
     *
     * @var PHP_Depend_Metrics_Dependency_Analyzer
     */
    protected $analyzer;

    /**
     * The temporary file name for the logger result.
     *
     * @var string
     */
    protected $resultFile;

    /**
     * Creates the package structure from a test source file.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resultFile = self::createRunResourceURI('pdepend-log.xml');
    }

    /**
     * Removes the temporary log files.
     *
     * @return void
     */
    protected function tearDown()
    {
        @unlink($this->resultFile);

        parent::tearDown();
    }

    /**
     * Tests that the logger returns the expected set of analyzers.
     *
     * @return void
     */
    public function testReturnsExceptedAnalyzers()
    {
        $logger    = new Xml();
        $actual    = $logger->getAcceptedAnalyzers();
        $expected = array('PHP_Depend_Metrics_Dependency_Analyzer');

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the logger throws an exception if the log target wasn't
     * configured.
     *
     * @return void
     */
    public function testThrowsExceptionForInvalidLogTarget()
    {
        $this->setExpectedException(
            '\PHP\Depend\Log\NoLogOutputException',
            "The log target is not configured for 'PHP\\Depend\\Log\\Jdepend\\Xml'."
        );

        $logger = new Xml();
        $logger->close();
    }

    /**
     * Tests that {@link \PHP\Depend\Log\Jdepend\Xml::write()} generates the
     * expected document structure for the source, but without any applied
     * metrics.
     *
     * @return void
     */
    public function testXmlLogWithoutMetrics()
    {
        $this->markTestIncomplete('@todo 2.0');

        $this->packages = self::parseCodeResourceForTest();

        $this->analyzer = new \PHP_Depend_Metrics_Dependency_Analyzer();
        $this->analyzer->analyze($this->packages);

        $log = new Xml();
        $log->setLogFile($this->resultFile);
        $log->setCode($this->packages);
        $log->log($this->analyzer);
        $log->close();

        $fileName = 'pdepend-log' . CORE_PACKAGE . '.xml';
        $this->assertXmlStringEqualsXmlString(
            $this->getNormalizedPathXml(__DIR__ . "/_expected/{$fileName}"),
            file_get_contents($this->resultFile)
        );
    }

    /**
     * testXmlLogAcceptsOnlyTheCorrectAnalyzer
     *
     * @return void
     */
    public function testXmlLogAcceptsOnlyTheCorrectAnalyzer()
    {
        $logger = new Xml();

        $this->assertFalse($logger->log(new DummyAnalyzer()));
        $this->assertTrue($logger->log(new \PHP_Depend_Metrics_Dependency_Analyzer()));
    }

    /**
     * Normalizes the file references within the expected result document.
     *
     * @param string $fileName File name of the expected result document.
     *
     * @return string
     */
    protected function getNormalizedPathXml($fileName)
    {
        $path = self::createCodeResourceUriForTest();

        return preg_replace(
            '(sourceFile="[^"]+/([^/"]+)")',
            'sourceFile="' . $path . '/\\1"',
            file_get_contents($fileName)
        );
    }

}
