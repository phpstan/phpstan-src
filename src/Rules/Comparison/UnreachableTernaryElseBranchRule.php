<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements Rule<Node\Expr\Ternary>
 */
class UnreachableTernaryElseBranchRule implements Rule
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
		return Node\Expr\Ternary::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->disable) {
			return [];
		}

		$conditionType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->cond) : $scope->getNativeType($node->cond);
		$conditionBooleanType = $conditionType->toBoolean();
		if (
			$conditionBooleanType->isTrue()->yes()
			&& $this->helper->shouldSkip($scope, $node->cond)
			&& !$this->helper->shouldReportAlwaysTrueByDefault($node->cond)
		) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $scope->getNativeType($node->cond);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
			};
			return [
				$addTip(RuleErrorBuilder::message('Else branch is unreachable because ternary operator condition is always true.'))
					->identifier('ternary.elseUnreachable')
					->line($node->else->getStartLine())
					->build(),
			];
		}

		return [];
	}

}
