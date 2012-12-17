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

namespace PHP\Depend\Metrics;

/**
 * Test case for the analyzer loader.
 *
 * @category  QualityAssurance
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://pdepend.org/
 *
 * @covers \PHP\Depend\Metrics\AnalyzerLoader
 * @group  pdepend
 * @group  pdepend::metrics
 * @group  unittest
 */
class AnalyzerLoaderTest extends AbstractTest
{
    /**
     * @var string
     */
    private $includePath;

    /**
     * Sets a temporary include path.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->includePath = set_include_path(
            substr(
                get_include_path(),
                0,
                strpos(
                    get_include_path(),
                    PATH_SEPARATOR,
                    1 + strpos(get_include_path(), PATH_SEPARATOR)
                )
            )
        );
    }

    /**
     * Resets the temporary include path.
     *
     * @return void
     */
    protected function tearDown()
    {
        set_include_path($this->includePath);

        parent::tearDown();
    }

    /**
     * Tests that the analyzer loader loads the correct analyzer instances.
     *
     * @return void
     */
    public function testLoadKnownAnalyzersByInstance()
    {
        $expected = array(
            'PHP\\Depend\\Metrics\\CodeRank\\Analyzer',
            'PHP\\Depend\\Metrics\\Inheritance\\Analyzer',
        );

        $loader = new AnalyzerLoader(
            new AnalyzerClassFileSystemLocator(),
            $this->getMock('\\PHP\\Depend\\Util\\Cache\\CacheDriver'),
            $expected
        );

        $actual = array();
        foreach ($loader->getAnalyzers() as $analyzers) {
            foreach ($analyzers as $analyzer) {
                $actual[] = get_class($analyzer);
            }
        }
        sort($actual);

        self::assertEquals($expected, $actual);
    }

    /**
     * testLoaderOnlyReturnsEnabledAnalyzerInstances
     *
     * @return void
     */
    public function testLoaderOnlyReturnsEnabledAnalyzerInstances()
    {
        $analyzer = $this->getMock('PHP\Depend\Metrics\Analyzer');
        $analyzer->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $reflection = $this->getMock('ReflectionObject', array('newInstance'), array($analyzer));
        $reflection->expects($this->once())
            ->method('newInstance')
            ->will($this->returnValue($analyzer));

        $locator = $this->getMock('PHP\\Depend\\Metrics\\AnalyzerClassLocator');
        $locator->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array($reflection)));

        $loader = new AnalyzerLoader(
            $locator,
            $this->getMock('\\PHP\\Depend\\Util\\Cache\\CacheDriver'),
            array('PHP\Depend\Metrics\Analyzer')
        );

        self::assertEquals(1, count($loader->getAnalyzers()));
    }


    /**
     * testLoaderNotReturnsDisabledAnalyzerInstances
     *
     * @return void
     */
    public function testLoaderNotReturnsDisabledAnalyzerInstances()
    {
        $analyzer = $this->getMock('PHP\Depend\Metrics\Analyzer');
        $analyzer->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        $reflection = $this->getMock('ReflectionObject', array('newInstance'), array($analyzer));
        $reflection->expects($this->once())
            ->method('newInstance')
            ->will($this->returnValue($analyzer));

        $locator = $this->getMock('PHP\\Depend\\Metrics\\AnalyzerClassLocator');
        $locator->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(array($reflection)));

        $loader = new AnalyzerLoader(
            $locator,
            $this->getMock('\\PHP\\Depend\\Util\\Cache\\CacheDriver'),
            array('PHP\Depend\Metrics\Analyzer')
        );

        self::assertEquals(0, count($loader->getAnalyzers()));
    }
}
