<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
class NumberComparisonOperatorsConstantConditionRule implements Rule
{

	public function __construct(
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return BinaryOp::class;
	}

	public function processNode(
		Node $node,
		Scope $scope,
	): array
	{
		if (
			!$node instanceof BinaryOp\Greater
			&& !$node instanceof BinaryOp\GreaterOrEqual
			&& !$node instanceof BinaryOp\Smaller
			&& !$node instanceof BinaryOp\SmallerOrEqual
		) {
			return [];
		}

		$exprType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
		if ($exprType instanceof ConstantBooleanType) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $scope->getNativeType($node);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
			};

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Comparison operation "%s" between %s and %s is always %s.',
					$node->getOperatorSigil(),
					$scope->getType($node->left)->describe(VerbosityLevel::value()),
					$scope->getType($node->right)->describe(VerbosityLevel::value()),
					$exprType->getValue() ? 'true' : 'false',
				)))->build(),
			];
		}

		return [];
	}

}
