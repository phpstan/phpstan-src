<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function in_array;

final class SocketObjectReturnTypeFunctionExtension implements DynamicFunctionReturnTypeExtension
{

	private const FUNCTIONS = [
		'socket_accept',
		'socket_addrinfo_bind',
		'socket_addrinfo_connect',
		'socket_create',
		'socket_create_listen',
		'socket_import_stream',
		'socket_wsaprotocol_info_import',
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), self::FUNCTIONS, true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if ($scope->getPhpVersion()->socketFunctionsUseObject()->yes()) {
			return new UnionType([new ConstantBooleanType(false), new ObjectType('\\Socket')]);
		}

		if ($scope->getPhpVersion()->socketFunctionsUseObject()->no()) {
			return new UnionType([new ConstantBooleanType(false), new ResourceType()]);
		}

		return null;
	}

}
