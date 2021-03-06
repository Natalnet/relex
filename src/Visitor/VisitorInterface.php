<?php
/*
 * This file is part of Tree library.
 *
 * (c) 2013 Nicolò Martini
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Natalnet\Relex\Visitor;

use Natalnet\Relex\Node\NodeInterface;

/**
 * Visitor interface for Nodes.
 *
 * @author     Nicolò Martini <nicmartnic@gmail.com>
 */
interface VisitorInterface
{
    /**
     * @param NodeInterface $node
     * @return mixed
     */
    public function visit(NodeInterface $node);
}
