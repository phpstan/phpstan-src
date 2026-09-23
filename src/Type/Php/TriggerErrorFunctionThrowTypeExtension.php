<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use ValueError;
use function count;
use function in_array;
use function is_int;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;

/**
 * trigger_error() itself throws only ValueError for an invalid error level, but the
 * registered error handler may throw anything. E_USER_ERROR terminates the script
 * unless the handler throws, so the call is modelled as throwing Throwable there,
 * the same way never-returning calls are.
 */
#[AutowiredService]
final class TriggerErrorFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	private const NON_FATAL_ERROR_LEVELS = [E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED];

	public function __construct(
		private PhpVersion $phpVersion,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'trigger_error';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		if (count($args) === 0) {
			return $functionReflection->getThrowType();
		}

		$errorHandlerThrowType = $this->implicitThrows ? new ObjectType(Throwable::class) : null;
		if (count($args) === 1) {
			return $errorHandlerThrowType;
		}

		$errorLevels = $scope->getType($args[1]->value)->getConstantScalarValues();
		if (count($errorLevels) === 0) {
			if ($errorHandlerThrowType !== null) {
				return $errorHandlerThrowType;
			}

			return $this->getInvalidErrorLevelThrowType();
		}

		$throwTypes = [];
		foreach ($errorLevels as $errorLevel) {
			if ($errorLevel === E_USER_ERROR) {
				$throwTypes[] = new ObjectType(Throwable::class);
				continue;
			}

			if (is_int($errorLevel) && in_array($errorLevel, self::NON_FATAL_ERROR_LEVELS, true)) {
				if ($errorHandlerThrowType !== null) {
					$throwTypes[] = $errorHandlerThrowType;
				}
				continue;
			}

			$invalidErrorLevelThrowType = $this->getInvalidErrorLevelThrowType();
			if ($invalidErrorLevelThrowType === null) {
				continue;
			}

			$throwTypes[] = $invalidErrorLevelThrowType;
		}

		if (count($throwTypes) === 0) {
			return null;
		}

		return TypeCombinator::union(...$throwTypes);
	}

	private function getInvalidErrorLevelThrowType(): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return null;
		}

		return new ObjectType(ValueError::class);
	}

}
