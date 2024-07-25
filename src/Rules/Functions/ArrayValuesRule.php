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
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class ArrayValuesRule implements Rule
{

	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		private readonly bool $treatPhpDocTypesAsCertain,
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

		if (AccessoryArrayListType::isListTypeEnabled() === false) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if ($functionReflection->getName() !== 'array_values') {
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
		if (count($args) === 0) {
			return [];
		}

		if ($this->treatPhpDocTypesAsCertain) {
			$arrayType = $scope->getType($args[0]->value);
		} else {
			$arrayType = $scope->getNativeType($args[0]->value);
		}

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			$message = 'Parameter #1 $array (%s) to function array_values is empty, call has no effect.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayValues.empty');
			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($args[0]->value);
				if (!$nativeArrayType->isIterableAtLeastOnce()->no()) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}
			}

			return [
				$errorBuilder->build(),
			];
		}

		if ($arrayType->isList()->yes()) {
			$message = 'Parameter #1 $array (%s) of array_values is already a list, call has no effect.';
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				$message,
				$arrayType->describe(VerbosityLevel::value()),
			))->identifier('arrayValues.list');
			if ($this->treatPhpDocTypesAsCertain) {
				$nativeArrayType = $scope->getNativeType($args[0]->value);
				if (!$nativeArrayType->isList()->yes()) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}
			}

			return [
				$errorBuilder->build(),
			];
		}

		return [];
	}

}
