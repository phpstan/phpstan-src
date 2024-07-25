<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;

final class TriggerErrorDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'trigger_error';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (count($args) === 0) {
			return null;
		}

		if (count($args) === 1) {
			return new ConstantBooleanType(true);
		}

		$errorType = $scope->getType($args[1]->value);

		if ($errorType instanceof ConstantIntegerType) {
			$errorLevel = $errorType->getValue();

			if ($errorLevel === E_USER_ERROR) {
				return new NeverType(true);
			}

			if (!in_array($errorLevel, [E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED], true)) {
				if ($this->phpVersion->throwsValueErrorForInternalFunctions()) {
					return new NeverType(true);
				}

				return new ConstantBooleanType(false);
			}

			return new ConstantBooleanType(true);
		}

		return null;
	}

}
