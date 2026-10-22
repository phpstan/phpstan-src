<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\Type;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;

#[AutowiredService]
final class ArrayFilterParameterClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'array_filter' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		$parameters = null;
		if (isset($args[2])) {
			$mode = $scope->getType($args[2]->value);
			if ($mode instanceof ConstantIntegerType) {
				if ($mode->getValue() === ARRAY_FILTER_USE_KEY) {
					$parameters = [$this->createParameter('key', $scope->getIterableKeyType($arrayType))];
				} elseif ($mode->getValue() === ARRAY_FILTER_USE_BOTH) {
					$parameters = [
						$this->createParameter('item', $scope->getIterableValueType($arrayType)),
						$this->createParameter('key', $scope->getIterableKeyType($arrayType)),
					];
				}
			}
		}

		$parameters ??= [$this->createParameter('item', $scope->getIterableValueType($arrayType))];

		return new ClosureType($parameters, new BooleanType());
	}

	private function createParameter(string $name, Type $type): NativeParameterReflection
	{
		return new NativeParameterReflection($name, false, $type, PassedByReference::createNo(), false, null);
	}

}
