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
use function sprintf;
//use function strpos; TODO: remove

/**
 * @implements Rule<Node\Expr\Yield_>
 */
class YieldTypeRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
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
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableKeyType(), $keyType);
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects key type %s, %s given.',
				$returnType->getIterableKeyType()->describe($verbosityLevel),
				$keyType->describe($verbosityLevel),
			))->build();
		}
		if (!$this->ruleLevelHelper->accepts($returnType->getIterableValueType(), $valueType, $scope->isDeclareStrictTypes())) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableValueType(), $valueType);

			// TODO: cleanup
			/* Disabled to let testing pass
			if (strpos($returnType->describe(VerbosityLevel::precise()), 'Bug6494') !== false) {
				echo "\nStart YieldTypeRule\n";

				echo 'Function return type class: ' . $returnType::class . "\n";
				// PHPStan\Type\Generic\GenericObjectType

				echo 'Function return type: ' . $returnType->describe(VerbosityLevel::precise()) . "\n";
				// Generator<int, static(Bug6494\Base), void, void>

				echo 'Function return iterable value type: ' . $returnType->getIterableValueType()->describe(VerbosityLevel::precise()) . "\n";
				// Generator<int, static(Bug6494\Base), void, void>
				// Which is the same as $returnType, incorrect!

				echo 'Value type: ' . $valueType->describe(VerbosityLevel::precise()) . "\n";
				// static(Bug6494\Base)
			}
			*/

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects value type %s, %s given.',
				$returnType->getIterableValueType()->describe($verbosityLevel),
				$valueType->describe($verbosityLevel),
			))->build();
		}
		if ($scope->getType($node) instanceof VoidType && !$scope->isInFirstLevelStatement()) {
			$messages[] = RuleErrorBuilder::message('Result of yield (void) is used.')->build();
		}

		return $messages;
	}

}
