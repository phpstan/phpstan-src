<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class PregReplaceCallbackArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'pregReplaceCallbackFlags';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Expr\FuncCall || !$node->name instanceof Node\Name || $node->isFirstClassCallable()) {
			return null;
		}

		$functionName = $node->name->toLowerString();

		if ($functionName === 'preg_replace_callback') {
			$args = $node->getArgs();
			if (isset($args[1]) && isset($args[5])) {
				$args[1]->setAttribute(self::ATTRIBUTE_NAME, $args[5]->value);
			}
		} elseif ($functionName === 'preg_replace_callback_array') {
			$args = $node->getArgs();
			if (!isset($args[0]) || !isset($args[4])) {
				return null;
			}
			$args[0]->setAttribute(self::ATTRIBUTE_NAME, $args[4]->value);

			// Also set the attribute on closures/arrow functions inside the array values
			$arrayArg = $args[0]->value;
			if ($arrayArg instanceof Node\Expr\Array_) {
				foreach ($arrayArg->items as $item) {
					if (!($item->value instanceof Node\Expr\Closure) && !($item->value instanceof Node\Expr\ArrowFunction)) {
						continue;
					}

					$item->value->setAttribute(self::ATTRIBUTE_NAME, $args[4]->value);
				}
			}
		}

		return null;
	}

}
