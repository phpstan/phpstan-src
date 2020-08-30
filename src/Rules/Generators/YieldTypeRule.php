<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generators;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Yield_>
 */
class YieldTypeRule implements Rule
{

	private RuleLevelHelper $ruleLevelHelper;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Yield_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$anonymousFunctionReturnType = $scope->getAnonymousFunctionReturnType();
		$scopeFunction = $scope->getFunction();
		if ($anonymousFunctionReturnType !== null) {
			$returnType = $anonymousFunctionReturnType;
		} elseif ($scopeFunction !== null) {
			$returnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
		} else {
			return []; // already reported by YieldInGeneratorRule
		}

		if ($returnType instanceof MixedType) {
			return [];
		}

		if ($node->key === null) {
			$keyType = new IntegerType();
		} else {
			$keyType = $scope->getType($node->key);
		}

		if ($node->value === null) {
			$valueType = new NullType();
		} else {
			$valueType = $scope->getType($node->value);
		}

		$messages = [];
		if (!$this->ruleLevelHelper->accepts($returnType->getIterableKeyType(), $keyType, $scope->isDeclareStrictTypes())) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableKeyType());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects key type %s, %s given.',
				$returnType->getIterableKeyType()->describe($verbosityLevel),
				$keyType->describe($verbosityLevel)
			))->build();
		}
		if (!$this->ruleLevelHelper->accepts($returnType->getIterableValueType(), $valueType, $scope->isDeclareStrictTypes())) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableValueType());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects value type %s, %s given.',
				$returnType->getIterableValueType()->describe($verbosityLevel),
				$valueType->describe($verbosityLevel)
			))->build();
		}
		if ($scope->getType($node) instanceof VoidType && !$scope->isInFirstLevelStatement()) {
			$messages[] = RuleErrorBuilder::message('Result of yield (void) is used.')->build();
		}

		return $messages;
	}

}
