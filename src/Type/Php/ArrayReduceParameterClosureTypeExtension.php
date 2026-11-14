<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

#[AutowiredService]
final class ArrayReduceParameterClosureTypeExtension implements FunctionParameterClosureTypeExtension
{

	public function __construct(
		private ArrayReduceCarryTypeResolver $carryTypeResolver,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'array_reduce' && $parameter->getName() === 'callback';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$resolved = $this->carryTypeResolver->resolve($functionCall, $scope);
		if ($resolved === null) {
			return null;
		}

		return new ClosureType([
			ArrayReduceCarryTypeResolver::createCallbackParameter('carry', $resolved['carry']),
			ArrayReduceCarryTypeResolver::createCallbackParameter('item', $resolved['item']),
		], new MixedType());
	}

}
