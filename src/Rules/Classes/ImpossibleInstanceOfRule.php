<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
class ImpossibleInstanceOfRule implements Rule
{

	public function __construct(
		private bool $checkAlwaysTrueInstanceof,
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$instanceofType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
		if (!$instanceofType instanceof ConstantBooleanType) {
			return [];
		}

		if ($node->class instanceof Node\Name) {
			$className = $scope->resolveName($node->class);
			$classType = new ObjectType($className);
		} else {
			$classType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->class) : $scope->getNativeType($node->class);
			$allowed = TypeCombinator::union(
				new StringType(),
				new ObjectWithoutClassType(),
			);
			if (!$allowed->accepts($classType, true)->yes()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Instanceof between %s and %s results in an error.',
						$scope->getType($node->expr)->describe(VerbosityLevel::typeOnly()),
						$classType->describe(VerbosityLevel::typeOnly()),
					))->build(),
				];
			}
		}

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

		if (!$instanceofType->getValue()) {
			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Instanceof between %s and %s will always evaluate to false.',
					$scope->getType($node->expr)->describe(VerbosityLevel::typeOnly()),
					$classType->describe(VerbosityLevel::getRecommendedLevelByType($classType)),
				)))->build(),
			];
		} elseif ($this->checkAlwaysTrueInstanceof) {
			if ($node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME) === true) {
				return [];
			}

			return [
				$addTip(RuleErrorBuilder::message(sprintf(
					'Instanceof between %s and %s will always evaluate to true.',
					$scope->getType($node->expr)->describe(VerbosityLevel::typeOnly()),
					$classType->describe(VerbosityLevel::getRecommendedLevelByType($classType)),
				)))->build(),
			];
		}

		return [];
	}

}
