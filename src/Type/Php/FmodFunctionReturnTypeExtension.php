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
use function abs;
use function fmod;
use function is_infinite;
use function is_nan;
use function max;
use function min;
use const NAN;

#[AutowiredService]
final class FmodFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'fmod';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0], $args[1])) {
			return null;
		}

		$leftMembers = $this->getFloatMembers($scope->getType($args[0]->value));
		$rightMembers = $this->getFloatMembers($scope->getType($args[1]->value));
		if ($leftMembers === null || $rightMembers === null) {
			return null;
		}

		$resultTypes = [];
		foreach ($leftMembers as $left) {
			foreach ($rightMembers as $right) {
				if ($left instanceof ConstantFloatType && $right instanceof ConstantFloatType) {
					$resultTypes[] = new ConstantFloatType(fmod($left->getValue(), $right->getValue()));
					continue;
				}

				$resultTypes[] = $this->getRemainderType($this->getBounds($left), $this->getBounds($right));
			}
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * @return list<ConstantFloatType|FloatRangeType>|null
	 */
	private function getFloatMembers(Type $type): ?array
	{
		if (!(new UnionType([new IntegerType(), new FloatType()]))->isSuperTypeOf($type)->yes()) {
			return null;
		}

		$members = [];
		$floatType = $type->toFloat();
		foreach ($floatType instanceof UnionType ? $floatType->getTypes() : [$floatType] as $member) {
			if (!$member instanceof ConstantFloatType && !$member instanceof FloatRangeType) {
				return null;
			}
			$members[] = $member;
		}

		return $members;
	}

	/**
	 * @return array{float, float}|null null for NAN
	 */
	private function getBounds(ConstantFloatType|FloatRangeType $type): ?array
	{
		if ($type instanceof FloatRangeType) {
			return [$type->getMin(), $type->getMax()];
		}

		$value = $type->getValue();

		return is_nan($value) ? null : [$value, $value];
	}

	/**
	 * The remainder has the sign of the dividend and a smaller magnitude
	 * than both operands. An infinite dividend or a zero divisor is NAN.
	 *
	 * @param array{float, float}|null $left
	 * @param array{float, float}|null $right
	 */
	private function getRemainderType(?array $left, ?array $right): Type
	{
		if ($left === null || $right === null) {
			return new ConstantFloatType(NAN);
		}

		[$leftMin, $leftMax] = $left;
		[$rightMin, $rightMax] = $right;
		$resultTypes = [];
		if (is_infinite($leftMin) || is_infinite($leftMax) || ($rightMin <= 0.0 && $rightMax >= 0.0)) {
			$resultTypes[] = new ConstantFloatType(NAN);
		}

		$leftHasFinite = $leftMin < $leftMax || !is_infinite($leftMin);
		$rightHasNonZero = $rightMin < 0.0 || $rightMax > 0.0;
		if ($leftHasFinite && $rightHasNonZero) {
			$divisorMagnitude = max(abs($rightMin), abs($rightMax));
			$resultTypes[] = FloatRangeType::fromInterval(
				$leftMin < 0.0 ? -min(-$leftMin, $divisorMagnitude) : 0.0,
				$leftMax > 0.0 ? min($leftMax, $divisorMagnitude) : 0.0,
			);
		}

		return TypeCombinator::union(...$resultTypes);
	}

}
