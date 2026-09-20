<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function array_slice;
use function count;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ArrayMapArgVisitor.cpp')]
final class ArrayMapArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'arrayMapArgs';

	public const PARAMETER_NAMES = ['callback', 'array', 'arrays'];

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'array_map') {
				$args = $node->getArgs();
				$callbackArg = ArgumentPositionHelper::getArgsByPosition($args, self::PARAMETER_NAMES)[0] ?? null;
				if ($callbackArg === null) {
					return null;
				}

				$arrayArgs = [];
				foreach ($args as $arg) {
					if ($arg === $callbackArg) {
						continue;
					}

					$arrayArgs[] = $arg;
				}

				if (count($arrayArgs) > 0) {
					$callbackArg->value->setAttribute(self::ATTRIBUTE_NAME, $arrayArgs);
				}
			}
		}
		return null;
	}

}
