<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class LineAttributesVisitor extends NodeVisitorAbstract
{

	public function __construct(private ?int $startLine, private ?int $endLine)
	{
	}

	#[Override]
	public function enterNode(Node $node): Node
	{
		if ($node->getStartLine() === -1) {
			$node->setAttribute('startLine', $this->startLine);
		}

		if ($node->getEndLine() === -1) {
			$node->setAttribute('endLine', $this->endLine);
		}

		return $node;
	}

}
