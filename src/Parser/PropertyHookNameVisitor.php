<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;
use function is_string;

final class PropertyHookNameVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'propertyName';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Property) {
			if (count($node->hooks) === 0) {
				return null;
			}

			$propertyName = null;
			foreach ($node->props as $prop) {
				$propertyName = $prop->name->toString();
				break;
			}

			if (!isset($propertyName)) {
				return null;
			}

			foreach ($node->hooks as $hook) {
				$hook->setAttribute(self::ATTRIBUTE_NAME, $propertyName);
			}

			return $node;
		}

		if ($node instanceof Node\Param) {
			if (count($node->hooks) === 0) {
				return null;
			}
			if (!$node->var instanceof Node\Expr\Variable) {
				return null;
			}
			if (!is_string($node->var->name)) {
				return null;
			}

			foreach ($node->hooks as $hook) {
				$hook->setAttribute(self::ATTRIBUTE_NAME, $node->var->name);
			}

			return $node;
		}

		return null;
	}

}
