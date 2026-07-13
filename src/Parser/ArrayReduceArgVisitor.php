<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class ArrayReduceArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'arrayReduceArgs';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'array_reduce') {
				$arrayArg = null;
				$callbackArg = null;
				$initialArg = null;
				foreach ($node->getArgs() as $i => $arg) {
					if ($arg->unpack) {
						return null;
					}
					$name = $arg->name !== null ? $arg->name->toString() : null;
					if ($name === 'array' || ($name === null && $i === 0)) {
						$arrayArg = $arg;
					} elseif ($name === 'callback' || ($name === null && $i === 1)) {
						$callbackArg = $arg;
					} elseif ($name === 'initial' || ($name === null && $i === 2)) {
						$initialArg = $arg;
					}
				}
				if ($arrayArg !== null && $callbackArg !== null) {
					$callbackArg->value->setAttribute(self::ATTRIBUTE_NAME, [$arrayArg, $initialArg]);
				}
			}
		}
		return null;
	}

}
