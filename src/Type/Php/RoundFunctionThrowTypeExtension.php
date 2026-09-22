<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use function count;

/**
 * round() throws ValueError only on PHP 8.4+, for an int $mode that is not
 * one of the eight rounding modes (1-8).
 */
#[AutowiredService]
final class RoundFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'round';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInvalidRoundingMode()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) < 3) {
			return new VoidType();
		}

		$validModeType = TypeCombinator::union(
			new ObjectType('RoundingMode'),
			IntegerRangeType::fromInterval(1, 8),
		);
		if ($validModeType->isSuperTypeOf($scope->getNativeType($args[2]->value))->yes()) {
			return new VoidType();
		}

		return $functionReflection->getThrowType();
	}

}
