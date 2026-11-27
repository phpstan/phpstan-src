<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use ValueError;
use function count;
use function in_array;

/**
 * min() and max() throw ValueError only when called with a single empty array.
 * Multiple arguments are just compared with each other.
 */
#[AutowiredService]
final class MinMaxFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['min', 'max'], true);
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		if (count($args) === 0) {
			return $functionReflection->getThrowType();
		}

		if ($scope->getPhpVersion()->throwsValueErrorForInternalFunctions()->no()) {
			return new VoidType();
		}

		$firstArgType = $scope->getNativeType($args[0]->value);
		if ($args[0]->unpack) {
			$firstArgType = $firstArgType->getIterableValueType();
		}

		if ($firstArgType->isArray()->no() || $firstArgType->isIterableAtLeastOnce()->yes()) {
			return new VoidType();
		}

		$argTypes = [];
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType() ?? new ObjectType(ValueError::class);
			}

			$argTypes[] = $scope->getNativeType($arg->value);
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromTypes($argTypes, $functionReflection->getVariants(), false);
		$parameters = $parametersAcceptor->getParameters();
		if (isset($parameters[0]) && $parameters[0]->getType()->isArray()->yes()) {
			return $functionReflection->getThrowType() ?? new ObjectType(ValueError::class);
		}

		return new VoidType();
	}

}
