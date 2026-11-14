<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements MultipleNodeTypesRule<Node>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 0),
	if: '%featureToggles.phpLanguageDeprecations%',
)]
final class GlobalsModificationRule implements MultipleNodeTypesRule
{

	public function getNodeTypes(): array
	{
		return [
			Expr\Assign::class,
			Expr\AssignOp::class,
			Expr\AssignRef::class,
			Expr\PreInc::class,
			Expr\PostInc::class,
			Expr\PreDec::class,
			Expr\PostDec::class,
			Stmt\Foreach_::class,
			Stmt\Unset_::class,
		];
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->restrictsGlobalsModification()->no()) {
			return [];
		}

		if ($node instanceof Stmt\Unset_) {
			$targets = $node->vars;
		} elseif ($node instanceof Stmt\Foreach_) {
			$targets = [$node->valueVar];
			if ($node->keyVar !== null) {
				$targets[] = $node->keyVar;
			}
		} elseif (
			$node instanceof Expr\Assign
			|| $node instanceof Expr\AssignOp
			|| $node instanceof Expr\AssignRef
			|| $node instanceof Expr\PreInc
			|| $node instanceof Expr\PostInc
			|| $node instanceof Expr\PreDec
			|| $node instanceof Expr\PostDec
		) {
			$targets = [$node->var];
		} else {
			return [];
		}

		foreach ($targets as $target) {
			if ($this->containsGlobals($target)) {
				return [
					RuleErrorBuilder::message('$GLOBALS can only be modified using the $GLOBALS[$name] = $value syntax.')
						->identifier('globals.modification')
						->nonIgnorable()
						->build(),
				];
			}
		}

		if ($node instanceof Expr\AssignRef && $this->isGlobals($node->expr)) {
			return [
				RuleErrorBuilder::message('Cannot acquire reference to $GLOBALS.')
					->identifier('globals.reference')
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

	private function containsGlobals(Expr $expr): bool
	{
		if ($this->isGlobals($expr)) {
			return true;
		}

		if (!$expr instanceof Expr\List_ && !$expr instanceof Expr\Array_) {
			return false;
		}

		foreach ($expr->items as $item) {
			if ($item === null) {
				continue;
			}

			if ($this->containsGlobals($item->value)) {
				return true;
			}
		}

		return false;
	}

	private function isGlobals(Expr $expr): bool
	{
		return $expr instanceof Expr\Variable && $expr->name === 'GLOBALS';
	}

}
