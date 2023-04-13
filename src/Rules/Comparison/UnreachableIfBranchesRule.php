<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements Rule<Node\Stmt\If_>
 */
class UnreachableIfBranchesRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private bool $treatPhpDocTypesAsCertain,
		private bool $disable,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\If_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->disable) {
			return [];
		}

		$errors = [];
		$condition = $node->cond;
		$conditionType = $this->treatPhpDocTypesAsCertain ? $scope->getType($condition) : $scope->getNativeType($condition);
		$conditionBooleanType = $conditionType->toBoolean();
		$nextBranchIsDead = $conditionBooleanType->isTrue()->yes() && $this->helper->shouldSkip($scope, $node->cond) && !$this->helper->shouldReportAlwaysTrueByDefault($node->cond);
		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, &$condition): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$booleanNativeType = $scope->getNativeType($condition)->toBoolean();
			if ($booleanNativeType instanceof ConstantBooleanType) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
		};

		foreach ($node->elseifs as $elseif) {
			if ($nextBranchIsDead) {
				$errors[] = $addTip(RuleErrorBuilder::message('Elseif branch is unreachable because previous condition is always true.')->line($elseif->getLine()))->build();
				continue;
			}

			$condition = $elseif->cond;
			$conditionType = $this->treatPhpDocTypesAsCertain ? $scope->getType($condition) : $scope->getNativeType($condition);
			$conditionBooleanType = $conditionType->toBoolean();
			$nextBranchIsDead = $conditionBooleanType->isTrue()->yes() && $this->helper->shouldSkip($scope, $elseif->cond) && !$this->helper->shouldReportAlwaysTrueByDefault($elseif->cond);
		}

		if ($node->else !== null && $nextBranchIsDead) {
			$errors[] = $addTip(RuleErrorBuilder::message('Else branch is unreachable because previous condition is always true.'))->line($node->else->getLine())->build();
		}

		return $errors;
	}

}
