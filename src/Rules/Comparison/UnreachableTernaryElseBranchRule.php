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

	private ConstantConditionRuleHelper $helper;

	private bool $treatPhpDocTypesAsCertain;

	public function __construct(
		ConstantConditionRuleHelper $helper,
		bool $treatPhpDocTypesAsCertain
	)
	{
		$this->helper = $helper;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Ternary::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$conditionType = $scope->getType($node->cond)->toBoolean();
		if (
			$conditionType instanceof ConstantBooleanType
			&& $conditionType->getValue()
			&& $this->helper->shouldSkip($scope, $node->cond)
			&& !$this->helper->shouldReportAlwaysTrueByDefault($node->cond)
		) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getType($node->cond);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
			};
			return [
				$addTip(RuleErrorBuilder::message('Else branch is unreachable because ternary operator condition is always true.'))
					->line($node->else->getLine())
					->identifier('deadCode.unreachableTernaryElse')
					->metadata([
						'statementDepth' => $node->getAttribute('statementDepth'),
						'statementOrder' => $node->getAttribute('statementOrder'),
						'depth' => $node->getAttribute('expressionDepth'),
						'order' => $node->getAttribute('expressionOrder'),
					])
					->build(),
			];
		}

		return [];
	}

}
