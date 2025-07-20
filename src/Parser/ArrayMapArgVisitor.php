<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function count;

#[AutowiredService]
final class ArrayMapArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'arrayMapArgs';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$this->isArrayMapCall($node)) {
			return null;
		}

		$args = $node->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$callbackArg = null;
		$arrayArgs = [];
		foreach ($args as $i => $arg) {
			if ($callbackArg === null) {
				if ($arg->name === null && $i === 0) {
					$callbackArg = $arg;
					continue;
				}
				if ($arg->name !== null && $arg->name->toString() === 'callback') {
					$callbackArg = $arg;
					continue;
				}
			}

			$arrayArgs[] = $arg;
		}

		if ($callbackArg !== null) {
			$callbackArg->value->setAttribute(self::ATTRIBUTE_NAME, $arrayArgs);
			return new Node\Expr\FuncCall(
				$node->name,
				[$callbackArg, ...$arrayArgs],
				$node->getAttributes(),
			);
		}

		return null;
	}

	/**
	 * @phpstan-assert-if-true Node\Expr\FuncCall $node
	 */
	private function isArrayMapCall(Node $node): bool
	{
		if (!$node instanceof Node\Expr\FuncCall) {
			return false;
		}
		if (!$node->name instanceof Node\Name) {
			return false;
		}
		if ($node->isFirstClassCallable()) {
			return false;
		}

		return $node->name->toLowerString() === 'array_map';
	}

}
