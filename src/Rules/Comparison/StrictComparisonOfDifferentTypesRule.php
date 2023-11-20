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
class StrictComparisonOfDifferentTypesRule implements Rule
{

	public function __construct(
		private bool $checkAlwaysTrueStrictComparison,
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
		if (!$node instanceof Node\Expr\BinaryOp\Identical && !$node instanceof Node\Expr\BinaryOp\NotIdentical) {
			return [];
		}

		$nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
		if (!$nodeType instanceof ConstantBooleanType) {
			return [];
		}

		$leftType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->left) : $scope->getNativeType($node->left);
		$rightType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->right) : $scope->getNativeType($node->right);

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
			if ($instanceofTypeWithoutPhpDocs instanceof ConstantBooleanType) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
		};

		if (!$nodeType->getValue()) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Strict comparison using %s between %s and %s will always evaluate to false.',
					$node->getOperatorSigil(),
					$leftType->describe(VerbosityLevel::value()),
					$rightType->describe(VerbosityLevel::value()),
				)))->identifier(sprintf('%s.alwaysFalse', $node instanceof Node\Expr\BinaryOp\Identical ? 'identical' : 'notIdentical'))->build(),
			];
		} elseif ($this->checkAlwaysTrueStrictComparison) {
			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
				return [];
			}

			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Strict comparison using %s between %s and %s will always evaluate to true.',
				$node->getOperatorSigil(),
				$leftType->describe(VerbosityLevel::value()),
				$rightType->describe(VerbosityLevel::value()),
			)));
			if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
				$errorBuilder->addTip('Remove remaining cases below this one and this error will disappear too.');
			}

			if (
				$leftType->isEnum()->yes()
				&& $rightType->isEnum()->yes()
				&& $node->getAttribute(LastConditionVisitor::ATTRIBUTE_IS_MATCH_NAME, false) !== true
			) {
				$errorBuilder->addTip('Use match expression instead. PHPStan will report unhandled enum cases.');
			}

			$errorBuilder->identifier(sprintf('%s.alwaysTrue', $node instanceof Node\Expr\BinaryOp\Identical ? 'identical' : 'notIdentical'));

			return [
				$errorBuilder->build(),
			];
		}

		return [];
	}

}
