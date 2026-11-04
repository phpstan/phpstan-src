<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function count;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ArrayMapArgVisitor.cpp')]
final class ArrayMapArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'arrayMapArgs';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'array_map') {
				$callbackArg = null;
				$arrayArgs = [];
				foreach ($node->getArgs() as $i => $arg) {
					if ($arg->name === null ? $i === 0 : $arg->name->toString() === 'callback') {
						$callbackArg = $arg;
						continue;
					}

					$arrayArgs[] = $arg;
				}
				if ($callbackArg !== null && count($arrayArgs) > 0) {
					$callbackArg->value->setAttribute(self::ATTRIBUTE_NAME, $arrayArgs);
				}
			}
		}
		return null;
	}

}
