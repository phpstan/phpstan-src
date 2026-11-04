<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/CurlSetOptArrayArgVisitor.cpp')]
final class CurlSetOptArrayArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isCurlSetOptArrayArg';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'curl_setopt_array') {
				foreach ($node->getArgs() as $i => $arg) {
					if ($arg->name === null ? $i !== 1 : $arg->name->toString() !== 'options') {
						continue;
					}

					$arg->setAttribute(self::ATTRIBUTE_NAME, true);
					break;
				}
			}
		}
		return null;
	}

}
