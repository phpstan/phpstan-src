<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<Stmt>
 */
class ContinueBreakInLoopRule implements Rule
{

	public function getNodeType(): string
	{
		return Stmt::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Stmt\Continue_ && !$node instanceof Stmt\Break_) {
			return [];
		}

		if (!$node->num instanceof Node\Scalar\LNumber) {
			$value = 1;
		} else {
			$value = $node->num->value;
		}

		$parent = $node->getAttribute('parent');
		while ($value > 0) {
			if (
				$parent === null
				|| $parent instanceof Stmt\Function_
				|| $parent instanceof Stmt\ClassMethod
				|| $parent instanceof Node\Expr\Closure
			) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Keyword %s used outside of a loop or a switch statement.',
						$node instanceof Stmt\Continue_ ? 'continue' : 'break',
					))->nonIgnorable()->build(),
				];
			}
			if (
				$parent instanceof Stmt\For_
				|| $parent instanceof Stmt\Foreach_
				|| $parent instanceof Stmt\Do_
				|| $parent instanceof Stmt\While_
			) {
				$value--;
			}
			if ($parent instanceof Stmt\Case_) {
				$value--;
				$parent = $parent->getAttribute('parent');
				if (!$parent instanceof Stmt\Switch_) {
					throw new ShouldNotHappenException();
				}
			}

			$parent = $parent->getAttribute('parent');
		}

		return [];
	}

}
