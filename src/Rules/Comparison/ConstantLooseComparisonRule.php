<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
final class ConstantLooseComparisonRule implements Rule
{

	public function __construct(
		private bool $checkAlwaysTrueLooseComparison,
		private bool $treatPhpDocTypesAsCertain,
		private bool $reportAlwaysTrueInLastCondition,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Expr\BinaryOp\Equal && !$node instanceof Node\Expr\BinaryOp\NotEqual) {
			return [];
		}

		$nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
		if (!$nodeType instanceof ConstantBooleanType) {
			return [];
		}

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
			if ($instanceofTypeWithoutPhpDocs instanceof ConstantBooleanType) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
		};

		if (!$nodeType->getValue()) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Loose comparison using %s between %s and %s will always evaluate to false.',
					$node->getOperatorSigil(),
					$scope->getType($node->left)->describe(VerbosityLevel::value()),
					$scope->getType($node->right)->describe(VerbosityLevel::value()),
				)))->identifier(sprintf('%s.alwaysFalse', $node instanceof Node\Expr\BinaryOp\Equal ? 'equal' : 'notEqual'))->build(),
			];
		} elseif ($this->checkAlwaysTrueLooseComparison) {
			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
				return [];
			}

			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Loose comparison using %s between %s and %s will always evaluate to true.',
				$node->getOperatorSigil(),
				$scope->getType($node->left)->describe(VerbosityLevel::value()),
				$scope->getType($node->right)->describe(VerbosityLevel::value()),
			)));
			if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
				$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
			}

			$errorBuilder->identifier(sprintf('%s.alwaysTrue', $node instanceof Node\Expr\BinaryOp\Equal ? 'equal' : 'notEqual'));

			return [$errorBuilder->build()];
		}

		return [];
	}

}
