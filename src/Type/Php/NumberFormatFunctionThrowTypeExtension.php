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

/**
 * number_format() throws ValueError only on PHP 8.6+, for $decimals outside the
 * range of a C int.
 */
#[AutowiredService]
final class NumberFormatFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	/** INT_MIN, written so that it stays an int on a 32-bit PHP */
	private const DECIMALS_MIN_LIMIT = -2147483647 - 1;

	/** INT_MAX */
	private const DECIMALS_MAX_LIMIT = 2147483647;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'number_format';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): Type
	{
		if ($scope->getPhpVersion()->throwsValueErrorForOutOfRangeIntegerArguments()->no()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return new ObjectType(ValueError::class);
			}
		}

		if (count($args) < 2) {
			return new VoidType();
		}

		$validDecimalsType = IntegerRangeType::fromInterval(self::DECIMALS_MIN_LIMIT, self::DECIMALS_MAX_LIMIT);
		if ($validDecimalsType->isSuperTypeOf($scope->getNativeType($args[1]->value))->yes()) {
			return new VoidType();
		}

		return new ObjectType(ValueError::class);
	}

}
