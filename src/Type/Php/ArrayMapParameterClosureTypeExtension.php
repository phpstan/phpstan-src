<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ArrayMapCallbackParameterTypes;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\UnpackedArgumentTypes;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function count;
use function in_array;

#[AutowiredService]
final class ArrayMapParameterClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'array_map' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = ArgumentsNormalizer::reorderArgs($functionReflection->getNamedArgumentsVariants()[0] ?? $functionReflection->getVariants()[0], $functionCall->getArgs()) ?? [];
		if (count($args) < 2) {
			return null;
		}

		$argTypes = [];
		$unpacked = [];
		$argCount = count($args);
		for ($i = 1; $i < $argCount; $i++) {
			$argTypes[] = $scope->getType($args[$i]->value);
			$unpacked[] = $args[$i]->unpack;
		}

		if (in_array(true, $unpacked, true)) {
			[$argTypes, $restType] = UnpackedArgumentTypes::expand($argTypes, $unpacked);
			if ($restType !== null) {
				// the number of arrays is not known
				return null;
			}
		}

		$valueTypes = [];
		foreach ($argTypes as $argType) {
			$valueTypes[] = $scope->getIterableValueType($argType);
		}

		$callbackParameters = [];
		foreach (ArrayMapCallbackParameterTypes::addPaddingNull($valueTypes, $argTypes) as $valueType) {
			$callbackParameters[] = $this->createParameter($valueType);
		}

		return new ClosureType($callbackParameters, new MixedType());
	}

	private function createParameter(Type $type): NativeParameterReflection
	{
		return new NativeParameterReflection('item', false, $type, PassedByReference::createNo(), false, null);
	}

}
