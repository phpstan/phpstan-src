<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ArrayFilterRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if ($functionReflection->getName() !== 'array_filter') {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		if (count($args) !== 1) {
			return [];
		}

		if ($this->treatPhpDocTypesAsCertain) {
			$arrayType = $scope->getType($args[0]->value);
		} else {
			$arrayType = $scope->getNativeType($args[0]->value);
		}

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			$message = 'Parameter #1 $array (%s) to function array_filter is empty, call has no effect.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayFilter.empty');
			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($args[0]->value);
				if (!$nativeArrayType->isIterableAtLeastOnce()->no()) {
					$errorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
				}
			}
			return [
				$errorBuilder->build(),
			];
		}

		$falsyType = StaticTypeFactory::falsey();
		$isSuperType = $falsyType->isSuperTypeOf($arrayType->getIterableValueType());

		if ($isSuperType->no()) {
			$message = 'Parameter #1 $array (%s) to function array_filter does not contain falsy values, the array will always stay the same.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayFilter.same');

			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($args[0]->value);
				$isNativeSuperType = $falsyType->isSuperTypeOf($nativeArrayType->getIterableValueType());
				if (!$isNativeSuperType->no()) {
					$errorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
				}
			}

			return [
				$errorBuilder->build(),
			];
		}

		if ($isSuperType->yes()) {
			$message = 'Parameter #1 $array (%s) to function array_filter contains falsy values only, the result will always be an empty array.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayFilter.alwaysEmpty');

			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($args[0]->value);
				$isNativeSuperType = $falsyType->isSuperTypeOf($nativeArrayType->getIterableValueType());
				if (!$isNativeSuperType->yes()) {
					$errorBuilder->tip('Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.');
				}
			}

			return [
				$errorBuilder->build(),
			];
		}

		return [];
	}

}
