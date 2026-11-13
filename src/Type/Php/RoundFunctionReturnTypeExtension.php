<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function ceil;
use function count;
use function floor;
use function in_array;
use function is_nan;
use const INF;

#[AutowiredService]
final class RoundFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(
			$functionReflection->getName(),
			[
				'round',
				'ceil',
				'floor',
			],
			true,
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		// PHP 7 can return either a float or false.
		// PHP 8 can either return a float or fatal.
		$defaultReturnType = null;

		$hasStricterRoundFunctions = $scope->getPhpVersion()->hasStricterRoundFunctions();

		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			// PHP 8 fatals with a missing parameter, PHP 7 returns null.
			if ($hasStricterRoundFunctions->yes()) {
				return new NeverType(true);
			}

			return new NullType();
		}

		$firstArgType = $scope->getType($args[0]->value);

		if ($firstArgType instanceof MixedType) {
			return $defaultReturnType;
		}

		if ($hasStricterRoundFunctions->yes()) {
			if (!$scope->isDeclareStrictTypes()) {
				$allowed = new UnionType([
					new IntegerType(),
					new FloatType(),
					new IntersectionType([
						new StringType(),
						new AccessoryNumericStringType(),
					]),
					new NullType(),
					new BooleanType(),
				]);
			} else {
				$allowed = new UnionType([
					new IntegerType(),
					new FloatType(),
				]);
			}

			if ($allowed->isSuperTypeOf($firstArgType)->no()) {
				// PHP 8 fatals if the parameter is not an integer or float.
				return new NeverType(true);
			}
		} elseif ($firstArgType->isArray()->yes()) {
			// PHP 7 returns false if the parameter is an array.
			return new ConstantBooleanType(false);
		}

		if (!(new UnionType([new IntegerType(), new FloatType()]))->isSuperTypeOf($firstArgType)->yes()) {
			return new FloatType();
		}

		$functionName = $functionReflection->getName();
		if ($functionName === 'round' && isset($args[1])) {
			$precisionType = $scope->getType($args[1]->value);
			if ($precisionType->getConstantScalarValues() !== [0]) {
				// another precision keeps the sign, or rounds to zero
				return $this->mapFloatBounds($firstArgType->toFloat(), static fn (float $value): float => $value < 0.0 ? -INF : 0.0, static fn (float $value): float => $value > 0.0 ? INF : 0.0);
			}
		}

		if ($functionName === 'ceil') {
			return $this->mapFloatBounds($firstArgType->toFloat(), static fn (float $value): float => ceil($value), static fn (float $value): float => ceil($value));
		}
		if ($functionName === 'floor') {
			return $this->mapFloatBounds($firstArgType->toFloat(), static fn (float $value): float => floor($value), static fn (float $value): float => floor($value));
		}

		// round() differs between PHP versions and modes near .5, but always lands on floor() or ceil()
		return $this->mapFloatBounds($firstArgType->toFloat(), static fn (float $value): float => floor($value), static fn (float $value): float => ceil($value));
	}

	/**
	 * Maps each range and constant of a float type through monotonic functions of its bounds.
	 *
	 * @param callable(float): float $mapMin
	 * @param callable(float): float $mapMax
	 */
	private function mapFloatBounds(Type $type, callable $mapMin, callable $mapMax): Type
	{
		$resultTypes = [];
		foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $member) {
			if ($member instanceof FloatRangeType) {
				$resultTypes[] = FloatRangeType::fromInterval($mapMin($member->getMin()), $mapMax($member->getMax()));
			} elseif ($member instanceof ConstantFloatType && !is_nan($member->getValue())) {
				$resultTypes[] = FloatRangeType::fromInterval($mapMin($member->getValue()), $mapMax($member->getValue()));
			} else {
				return new FloatType();
			}
		}

		return TypeCombinator::union(...$resultTypes);
	}

}
