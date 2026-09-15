<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/TraitCollectingVisitor.cpp')]
final class TraitCollectingVisitor extends NodeVisitorAbstract
{

	/** @var list<Node\Stmt\Trait_> */
	public array $traits = [];

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Stmt\Trait_) {
			return null;
		}

		$this->traits[] = $node;

		return null;
	}

}
