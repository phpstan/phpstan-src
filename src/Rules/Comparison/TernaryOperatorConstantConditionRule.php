<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Ternary>
 */
#[RegisteredRule(level: 4)]
final class TernaryOperatorConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Ternary::class;
	}

	public function processNode(
		Node $node,
		Scope $scope,
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->cond);
		if ($exprType instanceof ConstantBooleanType) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->cond);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
			};
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Ternary operator condition is always %s.',
					$exprType->getValue() ? 'true' : 'false',
				)))->identifier(sprintf('ternary.always%s', $exprType->getValue() ? 'True' : 'False'))->build(),
			];
		}

		return [];
	}

}
