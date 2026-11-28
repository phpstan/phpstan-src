<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use ValueError;
use function array_key_exists;
use function array_map;
use function count;

/**
 * array_change_key_case() and pathinfo() throw ValueError only on PHP 8.6+, for
 * a flag that is not one of the accepted values. Older versions accept any
 * integer.
 *
 * array_filter() does the same, but is not handled here: its implicit throw point
 * also stands for the exceptions of a callback whose body is unknown.
 */
#[AutowiredService]
final class InvalidFlagFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	/**
	 * Position of the flag argument and the values it accepts. The flags cannot
	 * be combined.
	 */
	private const FLAG_ARGUMENTS = [
		// CASE_LOWER, CASE_UPPER
		'array_change_key_case' => [1, [0, 1]],
		// PATHINFO_DIRNAME, PATHINFO_BASENAME, PATHINFO_EXTENSION, PATHINFO_FILENAME, PATHINFO_ALL
		'pathinfo' => [1, [1, 2, 4, 8, 15]],
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), self::FLAG_ARGUMENTS);
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): Type
	{
		if ($scope->getPhpVersion()->throwsValueErrorForInvalidFlagArguments()->no()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return new ObjectType(ValueError::class);
			}
		}

		$functionName = $functionReflection->getName();
		if (!array_key_exists($functionName, self::FLAG_ARGUMENTS)) {
			throw new ShouldNotHappenException();
		}

		[$position, $validValues] = self::FLAG_ARGUMENTS[$functionName];
		if (count($args) <= $position) {
			return new VoidType();
		}

		$validType = TypeCombinator::union(...array_map(
			static fn (int $value): Type => new ConstantIntegerType($value),
			$validValues,
		));
		if ($validType->isSuperTypeOf($scope->getNativeType($args[$position]->value))->yes()) {
			return new VoidType();
		}

		return new ObjectType(ValueError::class);
	}

}
