<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class RemoveNodeRule implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Stmt\Expression && self::isRemoveMeCall($node->expr)) {
			$toRemove = $node;
		} elseif ($node instanceof Stmt\ClassMethod && $node->name->toLowerString() === 'removeme') {
			$toRemove = $node;
		} elseif ($node instanceof Arg && $node->value instanceof ConstFetch && $node->value->name->toString() === 'REMOVE_ARG') {
			$toRemove = $node;
		} elseif ($node instanceof Stmt\If_ && self::isRemoveMeCall($node->cond)) {
			// not in an array, cannot be removed
			$toRemove = $node->cond;
		} else {
			return [];
		}

		return [
			RuleErrorBuilder::message('Removing ' . $toRemove->getType() . '.')
				->identifier('tests.removeNode')
				->line($toRemove->getStartLine())
				->removeNode($toRemove)
				->build(),
		];
	}

	private static function isRemoveMeCall(Node $node): bool
	{
		return $node instanceof FuncCall && $node->name instanceof Name && $node->name->toLowerString() === 'removeme';
	}

}
