<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
final class NumberComparisonOperatorsConstantConditionRule implements Rule
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

				return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
			};

			switch (get_class($node)) {
				case BinaryOp\Greater::class:
					$nodeType = 'greater';
					break;
				case BinaryOp\GreaterOrEqual::class:
					$nodeType = 'greaterOrEqual';
					break;
				case BinaryOp\Smaller::class:
					$nodeType = 'smaller';
					break;
				case BinaryOp\SmallerOrEqual::class:
					$nodeType = 'smallerOrEqual';
					break;
				default:
					throw new ShouldNotHappenException();
			}

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Comparison operation "%s" between %s and %s is always %s.',
					$node->getOperatorSigil(),
					$scope->getType($node->left)->describe(VerbosityLevel::value()),
					$scope->getType($node->right)->describe(VerbosityLevel::value()),
					$exprType->getValue() ? 'true' : 'false',
				)))->identifier(sprintf('%s.always%s', $nodeType, $exprType->getValue() ? 'True' : 'False'))->build(),
			];
		}

		return [];
	}

}
