<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class MagicConstantParamDefaultVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isMagicConstantParamDefault';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Param && $node->default instanceof Node\Scalar\MagicConst) {
			$node->default->setAttribute(self::ATTRIBUTE_NAME, true);
		}
		return null;
	}

}
