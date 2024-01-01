<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\BreaklessWhileLoopNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements Rule<BreaklessWhileLoopNode>
 */
class WhileLoopAlwaysTrueConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return BreaklessWhileLoopNode::class;
	}

	public function processNode(
		Node $node,
		Scope $scope,
	): array
	{
		foreach ($node->getExitPoints() as $exitPoint) {
			$statement = $exitPoint->getStatement();
			if ($statement instanceof Break_) {
				return [];
			}
			if (!$statement instanceof Continue_) {
				return [];
			}
			if ($statement->num === null) {
				continue;
			}
			if (!$statement->num instanceof Int_) {
				continue;
			}
			$value = $statement->num->value;
			if ($value === 1) {
				continue;
			}

			if ($value > 1) {
				return [];
			}
		}
		$originalNode = $node->getOriginalNode();
		$exprType = $this->helper->getBooleanType($scope, $originalNode->cond);
		if ($exprType->isTrue()->yes()) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $originalNode->cond);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
			};

			return [
				$addTip(RuleErrorBuilder::message('While loop condition is always true.'))->line($originalNode->cond->getStartLine())
					->identifier('while.alwaysTrue')
					->build(),
			];
		}

		return [];
	}

}
