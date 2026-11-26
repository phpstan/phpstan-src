<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use ValueError;
use function count;
use function in_array;
use function min;
use const PHP_INT_MAX;

/**
 * sleep() and usleep() throw ValueError since PHP 8.0 for a negative duration,
 * and since PHP 8.6 also for one that does not fit an unsigned int.
 */
#[AutowiredService]
final class SleepFunctionsThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	/** UINT_MAX / 1000, the limit of sleep() on Windows, which is lower than elsewhere */
	private const SLEEP_SECONDS_LIMIT = 4294967;

	/** UINT_MAX */
	private const USLEEP_MICROSECONDS_LIMIT = 4294967295;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sleep', 'usleep'], true);
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): Type
	{
		$phpVersions = $scope->getPhpVersion();
		if ($phpVersions->throwsValueErrorForInternalFunctions()->no()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		if (count($args) !== 1 || $args[0]->unpack || $args[0]->name !== null) {
			return new ObjectType(ValueError::class);
		}

		$max = null;
		if (!$phpVersions->throwsValueErrorForOutOfRangeIntegerArguments()->no()) {
			// UINT_MAX is a float on a 32-bit PHP, where every non-negative int is in range
			$max = $functionReflection->getName() === 'sleep'
				? self::SLEEP_SECONDS_LIMIT
				: min(self::USLEEP_MICROSECONDS_LIMIT, PHP_INT_MAX);
		}

		if (IntegerRangeType::fromInterval(0, $max)->isSuperTypeOf($scope->getNativeType($args[0]->value))->yes()) {
			return new VoidType();
		}

		return new ObjectType(ValueError::class);
	}

}
