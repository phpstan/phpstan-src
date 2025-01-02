<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function array_splice;
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

		$callbackPos = 0;
		if ($args[1]->name !== null && $args[1]->name->name === 'callback') {
			$callbackPos = 1;
		}
		$callbackArg = $args[$callbackPos];
		$arrayArgs = $args;
		array_splice($arrayArgs, $callbackPos, 1);
		$callbackArg->value->setAttribute(self::ATTRIBUTE_NAME, $arrayArgs);

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
