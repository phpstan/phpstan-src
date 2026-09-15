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

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'array_map') {
				$args = $node->getArgs();
				$arrayArgs = [];
				foreach ($args as $i => $arg) {
					if ($arg->name === null && $i === 0) {
						continue;
					}
					if ($arg->name !== null && $arg->name->toString() === 'callback') {
						continue;
					}

					$arrayArgs[] = $arg;
				}
				if (isset($args[0])) {
					$slicedArgs = array_slice($args, 1);
					if (count($slicedArgs) > 0) {
						$args[0]->value->setAttribute(self::ATTRIBUTE_NAME, $arrayArgs);
					}
				}
			}
		}
		return null;
	}

}
