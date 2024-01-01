<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\ParentStmtTypesVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_reverse;
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

		if (!$node->num instanceof Node\Scalar\Int_) {
			$value = 1;
		} else {
			$value = $node->num->value;
		}

		$parentStmtTypes = array_reverse($node->getAttribute(ParentStmtTypesVisitor::ATTRIBUTE_NAME));
		foreach ($parentStmtTypes as $parentStmtType) {
			if ($parentStmtType === Stmt\Case_::class) {
				continue;
			}
			if (
				$parentStmtType === Stmt\Function_::class
				|| $parentStmtType === Stmt\ClassMethod::class
				|| $parentStmtType === Node\Expr\Closure::class
			) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Keyword %s used outside of a loop or a switch statement.',
						$node instanceof Stmt\Continue_ ? 'continue' : 'break',
					))
						->nonIgnorable()
						->identifier(sprintf('%s.outOfLoop', $node instanceof Stmt\Continue_ ? 'continue' : 'break'))
						->build(),
				];
			}
			if (
				$parentStmtType === Stmt\For_::class
				|| $parentStmtType === Stmt\Foreach_::class
				|| $parentStmtType === Stmt\Do_::class
				|| $parentStmtType === Stmt\While_::class
				|| $parentStmtType === Stmt\Switch_::class
			) {
				$value--;
			}
			if ($value === 0) {
				break;
			}
		}

		if ($value > 0) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Keyword %s used outside of a loop or a switch statement.',
					$node instanceof Stmt\Continue_ ? 'continue' : 'break',
				))
					->nonIgnorable()
					->identifier(sprintf('%s.outOfLoop', $node instanceof Stmt\Continue_ ? 'continue' : 'break'))
					->build(),
			];
		}

		return [];
	}

}
