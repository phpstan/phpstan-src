<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/CurlSetOptArgVisitor.cpp')]
final class CurlSetOptArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isCurlSetOptArg';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isPartialFunctionApplication()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'curl_setopt') {
				$arg = ArgumentsNormalizer::getArgByPositionOrName($node->getArgs(), 0, 'handle');
				$arg?->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		}
		return null;
	}

}
