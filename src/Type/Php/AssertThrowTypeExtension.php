<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;
use function count;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/AssertThrowTypeExtension.cpp')]
final class AssertThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'assert';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		if (count($args) < 2) {
			return $functionReflection->getThrowType();
		}

		$customThrow = $scope->getType($args[1]->value);
		if ((new ObjectType(Throwable::class))->isSuperTypeOf($customThrow)->yes()) {
			return $customThrow;
		}

		return $functionReflection->getThrowType();
	}

}
