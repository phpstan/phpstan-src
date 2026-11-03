<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function intdiv;
use function is_infinite;
use function max;
use function min;
use const INF;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

#[AutowiredService]
final class IntdivFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'intdiv';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$numeratorIntervals = $this->getIntervals($scope->getType($args[0]->value)->toInteger());
		$divisorIntervals = $this->getIntervals($scope->getType($args[1]->value)->toInteger());
		if ($numeratorIntervals === null || $divisorIntervals === null) {
			return null;
		}

		$resultTypes = [];
		foreach ($numeratorIntervals as [$numeratorMin, $numeratorMax]) {
			foreach ($divisorIntervals as [$divisorMin, $divisorMax]) {
				// division by zero throws, so only the negative and the positive part of the divisor produce a result
				if ($divisorMin < 0) {
					$resultTypes[] = $this->divideIntervals($numeratorMin, $numeratorMax, $divisorMin, min($divisorMax, -1));
				}
				if ($divisorMax <= 0) {
					continue;
				}

				$resultTypes[] = $this->divideIntervals($numeratorMin, $numeratorMax, max($divisorMin, 1), $divisorMax);
			}
		}

		if (count($resultTypes) === 0) {
			return null;
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * Unbounded ends are represented by -INF and INF.
	 *
	 * @return list<array{int|float, int|float}>|null
	 */
	private function getIntervals(Type $type): ?array
	{
		$intervals = [];
		foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $innerType) {
			if ($innerType instanceof ConstantIntegerType) {
				$intervals[] = [$innerType->getValue(), $innerType->getValue()];
			} elseif ($innerType instanceof IntegerRangeType) {
				$intervals[] = [$innerType->getMin() ?? -INF, $innerType->getMax() ?? INF];
			} elseif ($innerType->isInteger()->yes()) {
				$intervals[] = [-INF, INF];
			} else {
				return null;
			}
		}

		return $intervals;
	}

	/**
	 * The divisor interval must not contain zero. The quotient is then monotonic in both operands
	 * and truncation towards zero is monotonic too, so the extremes are found in the corners.
	 */
	private function divideIntervals(int|float $numeratorMin, int|float $numeratorMax, int|float $divisorMin, int|float $divisorMax): Type
	{
		$results = [];
		foreach ([$numeratorMin, $numeratorMax] as $numerator) {
			foreach ([$divisorMin, $divisorMax] as $divisor) {
				$results[] = $this->divideBounds($numerator, $divisor);
			}
		}

		$min = min($results);
		$max = max($results);

		return IntegerRangeType::fromInterval(
			is_infinite($min) ? null : (int) $min,
			is_infinite($max) ? null : (int) $max,
		);
	}

	private function divideBounds(int|float $numerator, int|float $divisor): int|float
	{
		if (is_infinite($numerator)) {
			if (is_infinite($divisor)) {
				// the range already contains 0 through a neighbouring corner
				return 0;
			}

			return ($numerator > 0) === ($divisor > 0) ? INF : -INF;
		}

		if (is_infinite($divisor)) {
			return 0;
		}

		if ($numerator === PHP_INT_MIN && $divisor === -1) {
			// throws ArithmeticError, the closest valid result is intdiv(PHP_INT_MIN + 1, -1)
			return PHP_INT_MAX;
		}

		return intdiv((int) $numerator, (int) $divisor);
	}

}
