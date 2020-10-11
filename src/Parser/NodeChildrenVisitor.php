<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class NodeChildrenVisitor extends NodeVisitorAbstract
{

	/**
	 * @param Node $node
	 * @return null
	 */
	public function enterNode(Node $node)
	{
		$parentNode = $node->getAttribute('parent');
		if ($parentNode === null) {
			return null;
		}

		$parentChildren = $parentNode->getAttribute('children') ?? [];
		$parentChildren[] = $node;
		$parentNode->setAttribute('children', $parentChildren);

		return null;
	}

}
