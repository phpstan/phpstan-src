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

	public const PARAMETER_NAMES = ['handle', 'option', 'value'];

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'curl_setopt') {
				$args = ArgumentsNormalizer::getArgsByPosition($node->getArgs(), self::PARAMETER_NAMES);
				if (isset($args[0])) {
					$args[0]->setAttribute(self::ATTRIBUTE_NAME, true);
				}
			}
		}
		return null;
	}

}
