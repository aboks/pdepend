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

namespace PHP\Depend\AST;

use \PHPParser_Node_Stmt_ClassMethod;

/**
 * Custom AST node that represents a PHP method.
 *
 * @category  QualityAssurance
 * @author    Manuel Pichler <mapi@pdepend.org>
 * @copyright 2008-2012 Manuel Pichler. All rights reserved.
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://pdepend.org/
 * @since     2.0.0
 *
 * @property \PHP\Depend\AST\ASTType[] $thrownExceptions
 */
class ASTMethod extends PHPParser_Node_Stmt_ClassMethod implements ASTCallable
{
    /**
     * Reference context used to retrieve referenced nodes.
     *
     * @var \PHP\Depend\AST\ASTMethodRefs
     */
    public $refs;

    /**
     * Construct a new custom method node instance.
     *
     * @param \PHPParser_Node_Stmt_ClassMethod $method
     * @param \PHPParser_Node[] $subNodes
     * @param \PHP\Depend\AST\ASTMethodRefs $refs
     */
    public function __construct(
        PHPParser_Node_Stmt_ClassMethod $method,
        array $subNodes,
        ASTMethodRefs $refs)
    {
        parent::__construct(
            $method->name,
            array_merge(
                array(
                    'type'   => $method->type,
                    'byRef'  => $method->byRef,
                    'params' => $method->params,
                    'stmts'  => $method->stmts,
                ),
                $subNodes
            ),
            $method->attributes
        );

        $this->refs = $refs;


        $this->refs->initialize($this);
    }

    /**
     * Returns the global identifier for this node.
     *
     * @return string
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * Returns the name for this node.
     *
     * @return string
     */
    public function getName()
    {
        return (string) $this->name;
    }

    /**
     * Returns the namespace where this method is declared.
     *
     * @return \PHP\Depend\AST\ASTNamespace
     */
    public function getNamespace()
    {
        return $this->refs->getNamespace();
    }

    /**
     * Returns the declaring type for this method.
     *
     * @return \PHP\Depend\AST\ASTType
     */
    public function getDeclaringType()
    {
        return $this->refs->getDeclaringType();
    }

    /**
     * Returns a type that will be returned by this method or <b>NULL</b> when
     * this method does not return a type.
     *
     * @return \PHP\Depend\AST\ASTType|null
     */
    public function getReturnType()
    {
        return $this->refs->getReturnType();
    }

    /**
     * Returns an array with all exceptions thrown by this method.
     *
     * @return \PHP\Depend\AST\ASTType[]
     */
    public function getThrownExceptions()
    {
        return $this->thrownExceptions;
    }

    /**
     * Returns the source file that contains this ast fragment.
     *
     * @return string
     */
    public function getFile()
    {
        return $this->getAttribute('file');
    }

    /**
     * Returns the start line for this ast fragment.
     *
     * @return integer
     */
    public function getStartLine()
    {
        return $this->getAttribute('startLine', -1);
    }

    /**
     * Returns the start line for this ast fragment.
     *
     * @return integer
     */
    public function getEndLine()
    {
        return $this->getAttribute('endLine', -1);
    }

    /**
     * Magic wake up method that will register this object in the global node
     * reference context.
     *
     * @return void
     * @access private
     */
    public function __wakeup()
    {
        $this->refs->initialize($this);
    }
}
