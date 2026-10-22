<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ArrayMapParameterClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'array_map' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$callbackParameters = [];
		$argCount = count($args);
		for ($i = 1; $i < $argCount; $i++) {
			$arg = $args[$i];
			$arrayType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$constantArrays = $arrayType->getConstantArrays();
				if (count($constantArrays) === 0) {
					return null;
				}
				foreach ($constantArrays as $constantArray) {
					foreach ($constantArray->getValueTypes() as $valueType) {
						$callbackParameters[] = $this->createParameter($scope->getIterableValueType($valueType));
					}
				}
			} else {
				$callbackParameters[] = $this->createParameter($scope->getIterableValueType($arrayType));
			}
		}

		return new ClosureType($callbackParameters, new MixedType());
	}

	private function createParameter(Type $type): NativeParameterReflection
	{
		return new NativeParameterReflection('item', false, $type, PassedByReference::createNo(), false, null);
	}

}
