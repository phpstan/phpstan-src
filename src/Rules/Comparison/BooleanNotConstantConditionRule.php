<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BooleanNot>
 */
class BooleanNotConstantConditionRule implements \PHPStan\Rules\Rule
{

	/** @var ConstantConditionRuleHelper */
	private $helper;

	/** @var bool */
	private $treatPhpDocTypesAsCertain;

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
		return \PhpParser\Node\Expr\BooleanNot::class;
	}

	public function processNode(
		\PhpParser\Node $node,
		\PHPStan\Analyser\Scope $scope
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->expr);
		if ($exprType instanceof ConstantBooleanType) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->expr);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
			};

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Negated boolean expression is always %s.',
					$exprType->getValue() ? 'false' : 'true'
				)))->line($node->expr->getLine())->build(),
			];
		}

		return [];
	}

}
