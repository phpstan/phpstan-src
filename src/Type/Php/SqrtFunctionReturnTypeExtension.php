<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function max;
use function sqrt;
use const NAN;

#[AutowiredService]
final class SqrtFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'sqrt';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		if (!(new UnionType([new IntegerType(), new FloatType()]))->isSuperTypeOf($argType)->yes()) {
			return null;
		}

		// sqrt() is correctly rounded, so it keeps the order of its argument
		$resultTypes = [];
		$floatType = $argType->toFloat();
		foreach ($floatType instanceof UnionType ? $floatType->getTypes() : [$floatType] as $member) {
			if ($member instanceof ConstantFloatType) {
				$resultTypes[] = new ConstantFloatType(sqrt($member->getValue()));
			} elseif ($member instanceof FloatRangeType) {
				// the square root of a negative number is NAN, of -0.0 it is -0.0
				if ($member->getMin() < 0.0) {
					$resultTypes[] = new ConstantFloatType(NAN);
				}
				if ($member->getMax() >= 0.0) {
					$resultTypes[] = FloatRangeType::fromInterval(sqrt(max($member->getMin(), 0.0)), sqrt($member->getMax()));
				}
			} else {
				return null;
			}
		}

		return TypeCombinator::union(...$resultTypes);
	}

}
