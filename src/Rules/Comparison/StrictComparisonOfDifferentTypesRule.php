<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
final class StrictComparisonOfDifferentTypesRule implements Rule
{

	public function __construct(
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
		private bool $checkAlwaysTrueStrictComparison,
		private bool $treatPhpDocTypesAsCertain,
		private bool $reportAlwaysTrueInLastCondition,
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Expr\BinaryOp\Identical) {
			$nodeTypeResult = $this->richerScopeGetTypeHelper->getIdenticalResult($this->treatPhpDocTypesAsCertain ? $scope : $scope->doNotTreatPhpDocTypesAsCertain(), $node);
		} elseif ($node instanceof Node\Expr\BinaryOp\NotIdentical) {
			$nodeTypeResult = $this->richerScopeGetTypeHelper->getNotIdenticalResult($this->treatPhpDocTypesAsCertain ? $scope : $scope->doNotTreatPhpDocTypesAsCertain(), $node);
		} else {
			return [];
		}

		$nodeType = $nodeTypeResult->type;
		if (!$nodeType instanceof ConstantBooleanType) {
			return [];
		}

		$leftType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->left) : $scope->getNativeType($node->left);
		$rightType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->right) : $scope->getNativeType($node->right);

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $nodeTypeResult): RuleErrorBuilder {
			$reasons = $nodeTypeResult->reasons;
			if (count($reasons) > 0) {
				return $ruleErrorBuilder->acceptsReasonsTip($reasons);
			}

			if (!$this->treatPhpDocTypesAsCertain) {
				return $ruleErrorBuilder;
			}

			$instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
			if ($instanceofTypeWithoutPhpDocs instanceof ConstantBooleanType) {
				return $ruleErrorBuilder;
			}
			if (!$this->treatPhpDocTypesAsCertainTip) {
				return $ruleErrorBuilder;
			}

			return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
		};

		$verbosity = VerbosityLevel::value();
		if (
			(
				$leftType->isConstantScalarValue()->yes()
				&& !$leftType->isString()->no()
				&& !$rightType->isConstantScalarValue()->yes()
				&& !$rightType->isString()->no()
				&& TrinaryLogic::extremeIdentity($leftType->isLowercaseString(), $rightType->isLowercaseString())->maybe()
			) || (
				$rightType->isConstantScalarValue()->yes()
				&& !$rightType->isString()->no()
				&& !$leftType->isConstantScalarValue()->yes()
				&& !$leftType->isString()->no()
				&& TrinaryLogic::extremeIdentity($leftType->isLowercaseString(), $rightType->isLowercaseString())->maybe()
			)
		) {
			$verbosity = VerbosityLevel::precise();
		}

		if (!$nodeType->getValue()) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Strict comparison using %s between %s and %s will always evaluate to false.',
					$node->getOperatorSigil(),
					$leftType->describe($verbosity),
					$rightType->describe($verbosity),
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
				$leftType->describe($verbosity),
				$rightType->describe($verbosity),
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
