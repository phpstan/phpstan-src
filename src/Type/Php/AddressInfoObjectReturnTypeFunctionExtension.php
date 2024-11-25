<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function in_array;

final class AddressInfoObjectReturnTypeFunctionExtension implements DynamicFunctionReturnTypeExtension
{

	private const FUNCTIONS = [
		'socket_addrinfo_lookup',
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), self::FUNCTIONS, true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if ($scope->getPhpVersion()->socketFunctionsUseObject()->yes()) {
			return new UnionType([new ConstantBooleanType(false), new ArrayType(new MixedType(), new ObjectType('\\AddressInfo'))]);
		}

		if ($scope->getPhpVersion()->socketFunctionsUseObject()->no()) {
			return new UnionType([new ConstantBooleanType(false), new ArrayType(new MixedType(), new ResourceType())]);
		}

		return null;
	}

}
