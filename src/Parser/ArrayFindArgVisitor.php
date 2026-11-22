<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function in_array;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ArrayFindArgVisitor.cpp')]
final class ArrayFindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isArrayFindArg';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isPartialFunctionApplication()) {
			$functionName = $node->name->toLowerString();
			if (in_array($functionName, ['array_all', 'array_any', 'array_find', 'array_find_key'], true)) {
				$arg = ArgumentsNormalizer::getArgByPositionOrName($node->getArgs(), 0, 'array');
				$arg?->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		}
		return null;
	}

}
