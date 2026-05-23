<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use Throwable;
use function count;
use function in_array;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;

#[AutowiredService]
final class TriggerErrorDynamicThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['trigger_error', 'user_error'], true);
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();

		if (count($args) < 2) {
			return new VoidType();
		}

		$errorType = $scope->getType($args[1]->value);

		if ($errorType instanceof ConstantIntegerType) {
			$errorLevel = $errorType->getValue();

			if ($errorLevel === E_USER_ERROR) {
				return new ObjectType(Throwable::class);
			}

			if (in_array($errorLevel, [E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED], true)) {
				return new VoidType();
			}
		}

		return null;
	}

}
