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
use const PHP_INT_MIN;

#[AutowiredService]
final class IntdivFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const RANGE_COMBINATIONS_LIMIT = 16;

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

		$numeratorRanges = self::getRanges($scope->getType($args[0]->value)->toInteger());
		$divisorRanges = self::getRanges($scope->getType($args[1]->value)->toInteger());
		if ($numeratorRanges === null || $divisorRanges === null) {
			return null;
		}

		if (count($numeratorRanges) * count($divisorRanges) > self::RANGE_COMBINATIONS_LIMIT) {
			$numeratorRanges = [self::hull($numeratorRanges)];
			$divisorRanges = [self::hull($divisorRanges)];
		}

		$resultTypes = [];
		foreach ($numeratorRanges as $numeratorRange) {
			foreach ($divisorRanges as $divisorRange) {
				foreach (self::withoutZero($divisorRange) as $signedDivisorRange) {
					$resultTypes[] = self::divideRanges($numeratorRange, $signedDivisorRange);
				}
			}
		}

		if ($resultTypes === []) {
			// the divisor is always zero, so the call always throws
			return null;
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * Bounds of every integer range the type is built from, with an unbounded side
	 * represented by -INF/INF. Null when the type is not built from integers only.
	 *
	 * @return non-empty-list<array{int|float, int|float}>|null
	 */
	private static function getRanges(Type $type): ?array
	{
		$innerTypes = $type instanceof UnionType ? $type->getTypes() : [$type];

		$ranges = [];
		foreach ($innerTypes as $innerType) {
			if ($innerType instanceof IntegerRangeType) {
				$ranges[] = [$innerType->getMin() ?? -INF, $innerType->getMax() ?? INF];
			} elseif ($innerType instanceof ConstantIntegerType) {
				$ranges[] = [$innerType->getValue(), $innerType->getValue()];
			} elseif ($innerType->isInteger()->yes()) {
				$ranges[] = [-INF, INF];
			} else {
				return null;
			}
		}

		if ($ranges === []) {
			return null;
		}

		return $ranges;
	}

	/**
	 * @param non-empty-list<array{int|float, int|float}> $ranges
	 * @return array{int|float, int|float}
	 */
	private static function hull(array $ranges): array
	{
		$min = INF;
		$max = -INF;
		foreach ($ranges as [$rangeMin, $rangeMax]) {
			$min = min($min, $rangeMin);
			$max = max($max, $rangeMax);
		}

		return [$min, $max];
	}

	/**
	 * Splits a divisor range into its negative and its positive part. Zero is dropped
	 * because dividing by it throws instead of producing a value.
	 *
	 * @param array{int|float, int|float} $range
	 * @return list<array{int|float, int|float}>
	 */
	private static function withoutZero(array $range): array
	{
		[$min, $max] = $range;

		$parts = [];
		if ($min < 0) {
			$parts[] = [$min, min($max, -1)];
		}
		if ($max > 0) {
			$parts[] = [max($min, 1), $max];
		}

		return $parts;
	}

	/**
	 * Truncated division is monotonic in both operands as long as the divisor does not
	 * change sign, so the extremes of the result are always found at the corners.
	 *
	 * @param array{int|float, int|float} $numerator
	 * @param array{int|float, int|float} $divisor never containing zero
	 */
	private static function divideRanges(array $numerator, array $divisor): Type
	{
		$min = INF;
		$max = -INF;
		foreach ($numerator as $numeratorBound) {
			foreach ($divisor as $divisorBound) {
				$quotient = self::divideBounds($numeratorBound, $divisorBound);
				if ($quotient === null) {
					continue;
				}

				$min = min($min, $quotient);
				$max = max($max, $quotient);
			}
		}

		return IntegerRangeType::fromInterval(
			is_infinite($min) ? null : (int) $min,
			is_infinite($max) ? null : (int) $max,
		);
	}

	/**
	 * @return int|float|null INF/-INF when the quotient is unbounded, null when it is
	 * indeterminate because both operands are unbounded. An indeterminate corner is always
	 * bracketed by the remaining corners, so skipping it does not narrow the result.
	 */
	private static function divideBounds(int|float $numerator, int|float $divisor): int|float|null
	{
		$numeratorIsUnbounded = is_infinite($numerator);
		$divisorIsUnbounded = is_infinite($divisor);

		if ($numeratorIsUnbounded && $divisorIsUnbounded) {
			return null;
		}
		if ($divisorIsUnbounded) {
			return 0;
		}
		if ($numeratorIsUnbounded) {
			return $divisor < 0 ? -$numerator : $numerator;
		}

		// PHP_INT_MIN / -1 overflows, intdiv() throws instead of returning a value
		if ($numerator === PHP_INT_MIN && $divisor === -1) {
			return INF;
		}

		return intdiv((int) $numerator, (int) $divisor);
	}

}
