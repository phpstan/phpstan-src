<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use UnhandledMatchError;
use function array_map;
use function count;
use function sprintf;

/**
 * @implements Rule<MatchExpressionNode>
 */
class MatchExpressionRule implements Rule
{

	public function __construct(private bool $checkAlwaysTrueStrictComparison)
	{
	}

	public function getNodeType(): string
	{
		return MatchExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$matchCondition = $node->getCondition();
		$nextArmIsDead = false;
		$errors = [];
		$armsCount = count($node->getArms());
		$hasDefault = false;
		foreach ($node->getArms() as $i => $arm) {
			if ($nextArmIsDead) {
				$errors[] = RuleErrorBuilder::message('Match arm is unreachable because previous comparison is always true.')->line($arm->getLine())->build();
				continue;
			}
			$armConditions = $arm->getConditions();
			if (count($armConditions) === 0) {
				$hasDefault = true;
			}
			foreach ($armConditions as $armCondition) {
				$armConditionScope = $armCondition->getScope();
				$armConditionExpr = new Node\Expr\BinaryOp\Identical(
					$matchCondition,
					$armCondition->getCondition(),
				);
				$armConditionResult = $armConditionScope->getType($armConditionExpr);
				if (!$armConditionResult instanceof ConstantBooleanType) {
					continue;
				}

				$armLine = $armCondition->getLine();
				if (!$armConditionResult->getValue()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Match arm comparison between %s and %s is always false.',
						$armConditionScope->getType($matchCondition)->describe(VerbosityLevel::value()),
						$armConditionScope->getType($armCondition->getCondition())->describe(VerbosityLevel::value()),
					))->line($armLine)->build();
				} else {
					$nextArmIsDead = true;
					if (
						$this->checkAlwaysTrueStrictComparison
						&& ($i !== $armsCount - 1 || $i === 0)
					) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Match arm comparison between %s and %s is always true.',
							$armConditionScope->getType($matchCondition)->describe(VerbosityLevel::value()),
							$armConditionScope->getType($armCondition->getCondition())->describe(VerbosityLevel::value()),
						))->line($armLine)->build();
					}
				}
			}
		}

		if (!$hasDefault && !$nextArmIsDead) {
			$remainingType = $node->getEndScope()->getType($matchCondition);
			if (!$remainingType instanceof NeverType && !$this->isUnhandledMatchErrorCaught($node)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Match expression does not handle remaining %s: %s',
					$remainingType instanceof UnionType ? 'values' : 'value',
					$remainingType->describe(VerbosityLevel::value()),
				))->build();
			}
		}

		return $errors;
	}

	private function isUnhandledMatchErrorCaught(MatchExpressionNode $node): bool
	{
		$tryCatchNode = $node->getAttribute('parent');
		while (
			$tryCatchNode !== null &&
			!$tryCatchNode instanceof Node\FunctionLike &&
			!$tryCatchNode instanceof Node\Stmt\TryCatch
		) {
			$tryCatchNode = $tryCatchNode->getAttribute('parent');
		}

		if ($tryCatchNode === null || $tryCatchNode instanceof Node\FunctionLike) {
			return false;
		}

		foreach ($tryCatchNode->catches as $catch) {
			$catchType = TypeCombinator::union(...array_map(static fn (Node\Name $class): ObjectType => new ObjectType($class->toString()), $catch->types));
			if ($catchType->isSuperTypeOf(new ObjectType(UnhandledMatchError::class))->yes()) {
				return true;
			}
		}

		return false;
	}

}
