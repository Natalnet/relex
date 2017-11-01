<?php

namespace Natalnet\Relex\Visitor;

use Natalnet\Relex\Node\NodeInterface;

class PreOrderVisitor implements VisitorInterface
{
    public function visit(NodeInterface $node)
    {
        $nodes = [
            $node,
        ];

        foreach ($node->getChildren() as $child) {
            $nodes = array_merge(
                $nodes,
                $child->accept($this)
            );
        }

        return $nodes;
    }
}
