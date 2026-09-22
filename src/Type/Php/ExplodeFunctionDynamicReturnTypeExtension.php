<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_keys;
use function count;
use function explode;
use function max;
use function min;
use function substr_count;

#[AutowiredService]
final class ExplodeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/**
	 * Functions whose bool result proves the delimiter is a literal substring
	 * of the string, so the result of exploding it has at least two elements.
	 */
	private const SUBSTRING_PROVING_FUNCTIONS = [
		'str_contains',
		'str_starts_with',
		'str_ends_with',
	];

	/**
	 * How many delimiter/string/limit combinations may be evaluated when
	 * constant-folding the call before giving up on the exact result.
	 */
	private const CONSTANT_COMBINATION_LIMIT = 16;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'explode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$phpVersions = $scope->getPhpVersion();
		$delimiterType = $scope->getType($args[0]->value);
		$isEmptyString = (new ConstantStringType(''))->isSuperTypeOf($delimiterType);
		if ($isEmptyString->yes()) {
			if ($phpVersions->throwsTypeErrorForInternalFunctions()->yes()) {
				return new NeverType();
			}
			return new ConstantBooleanType(false);
		}

		$stringType = $scope->getType($args[1]->value);
		$accessory = [];
		if ($stringType->isLowercaseString()->yes()) {
			$accessory[] = new AccessoryLowercaseStringType();
		}
		if ($stringType->isUppercaseString()->yes()) {
			$accessory[] = new AccessoryUppercaseStringType();
		}
		if (count($accessory) > 0) {
			$accessory[] = new StringType();
			$returnValueType = new IntersectionType($accessory);
		} else {
			$returnValueType = new StringType();
		}

		$limitType = isset($args[2]) ? $scope->getType($args[2]->value) : null;

		$constantType = $this->createConstantSplitType($delimiterType, $stringType, $limitType, $phpVersions);
		if ($constantType !== null) {
			return $constantType;
		}

		$delimiterGuaranteedPresent = $this->isDelimiterGuaranteedPresent($args, $scope);

		if ($this->isSingleElementLimit($limitType)) {
			$returnType = $this->createSingleElementSplitType($stringType->toString());
		} elseif (
			$delimiterGuaranteedPresent
			&& ($limitType === null || IntegerRangeType::fromInterval(2, null)->isSuperTypeOf($limitType)->yes())
		) {
			$returnType = $this->createGuaranteedSplitType($returnValueType, $limitType);
		} else {
			$returnType = new IntersectionType([new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $returnValueType), new AccessoryArrayListType()]);

			if (
				$limitType === null
				|| IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($limitType)->yes()
				|| ($delimiterGuaranteedPresent && $this->isMinusOneLimit($limitType))
			) {
				$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
			}
		}

		if (!$phpVersions->throwsValueErrorForInternalFunctions()->yes() && $isEmptyString->maybe()) {
			$returnType = new UnionType([$returnType, new ConstantBooleanType(false)]);
		}

		if ($delimiterType instanceof MixedType) {
			$returnType = TypeUtils::toBenevolentUnion($returnType);
		}

		return $returnType;
	}

	/**
	 * @param Arg[] $args
	 */
	private function isDelimiterGuaranteedPresent(array $args, Scope $scope): bool
	{
		$delimiter = $args[0]->value;
		$haystack = $args[1]->value;

		foreach (self::SUBSTRING_PROVING_FUNCTIONS as $functionName) {
			$condition = new FuncCall(new FullyQualified($functionName), [
				new Arg($haystack),
				new Arg($delimiter),
			]);

			if ($scope->getType($condition)->isTrue()->yes()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The exact result of the split when the delimiter, the string and the limit
	 * are all known constants, or null when it cannot be computed.
	 */
	private function createConstantSplitType(Type $delimiterType, Type $stringType, ?Type $limitType, PhpVersions $phpVersions): ?Type
	{
		$delimiters = [];
		$hasEmptyDelimiter = false;
		foreach ($delimiterType->getConstantStrings() as $delimiterString) {
			$delimiterValue = $delimiterString->getValue();
			if ($delimiterValue === '') {
				// explode() does not split on an empty separator: it throws
				// a ValueError on PHP 8+, and returns false before that
				$hasEmptyDelimiter = true;
				continue;
			}

			$delimiters[] = $delimiterValue;
		}

		if (count($delimiters) === 0) {
			return null;
		}

		$strings = $stringType->getConstantStrings();
		if (count($strings) === 0) {
			return null;
		}

		if (count($delimiters) * count($strings) > self::CONSTANT_COMBINATION_LIMIT) {
			return null;
		}

		$results = [];
		foreach ($delimiters as $delimiter) {
			foreach ($strings as $string) {
				$stringValue = $string->getValue();
				$limits = $this->getDistinctLimits($limitType, substr_count($stringValue, $delimiter) + 1);
				if ($limits === null) {
					return null;
				}

				foreach ($limits as $limit) {
					if (count($results) >= self::CONSTANT_COMBINATION_LIMIT) {
						return null;
					}

					$items = explode($delimiter, $stringValue, $limit);
					if (count($items) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
						return null;
					}

					$builder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($items as $i => $item) {
						$builder->setOffsetValueType(new ConstantIntegerType($i), new ConstantStringType($item));
					}

					$results[] = $builder->getArray();
				}
			}
		}

		if ($hasEmptyDelimiter && !$phpVersions->throwsValueErrorForInternalFunctions()->yes()) {
			$results[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::union(...$results);
	}

	/**
	 * The limits that lead to different results when splitting a string into
	 * $partsCount parts, or null when they cannot be enumerated.
	 *
	 * Limits are clamped into the [-$partsCount, $partsCount] window because
	 * every limit above $partsCount produces the full split and every limit at
	 * or below -$partsCount produces an empty array. That keeps wide and even
	 * unbounded integer ranges enumerable.
	 *
	 * @return list<int>|null
	 */
	private function getDistinctLimits(?Type $limitType, int $partsCount): ?array
	{
		if ($limitType === null) {
			return [$partsCount];
		}

		if (!$limitType->isInteger()->yes()) {
			return null;
		}

		$finiteTypes = $limitType->getFiniteTypes();
		if (count($finiteTypes) > 0) {
			$clampedLimits = [];
			foreach ($finiteTypes as $finiteType) {
				if (!$finiteType instanceof ConstantIntegerType) {
					return null;
				}

				$value = $finiteType->getValue();
				$clampedLimits[max(-$partsCount, min($partsCount, $value))] = true;
			}

			return array_keys($clampedLimits);
		}

		$limits = [];
		for ($limit = -$partsCount; $limit <= $partsCount; $limit++) {
			if ($limit === -$partsCount) {
				$candidateLimitType = IntegerRangeType::fromInterval(null, $limit);
			} elseif ($limit === $partsCount) {
				$candidateLimitType = IntegerRangeType::fromInterval($limit, null);
			} else {
				$candidateLimitType = new ConstantIntegerType($limit);
			}

			if ($candidateLimitType->isSuperTypeOf($limitType)->no()) {
				continue;
			}

			$limits[] = $limit;
			if (count($limits) > self::CONSTANT_COMBINATION_LIMIT) {
				return null;
			}
		}

		return $limits;
	}

	/**
	 * A limit of 0 or 1 returns the whole string as the only element, without
	 * splitting on the delimiter.
	 */
	private function isSingleElementLimit(?Type $limitType): bool
	{
		return $limitType !== null
			&& IntegerRangeType::fromInterval(0, 1)->isSuperTypeOf($limitType)->yes();
	}

	private function isMinusOneLimit(?Type $limitType): bool
	{
		return $limitType !== null
			&& (new ConstantIntegerType(-1))->isSuperTypeOf($limitType)->yes();
	}

	/** The whole string, unsplit, as the only element. */
	private function createSingleElementSplitType(Type $valueType): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantIntegerType(0), $valueType);

		return $builder->getArray();
	}

	/**
	 * The delimiter is guaranteed to occur in the string, so the result has at
	 * least two elements. With a positive $limit the result has at most $limit
	 * elements.
	 */
	private function createGuaranteedSplitType(Type $valueType, ?Type $limitType): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantIntegerType(0), $valueType);
		$builder->setOffsetValueType(new ConstantIntegerType(1), $valueType);

		$maxElements = $this->getMaximumElementCount($limitType);
		if ($maxElements === null) {
			$builder->makeUnsealed(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $valueType);
		} else {
			for ($i = 2; $i < $maxElements; $i++) {
				$builder->setOffsetValueType(new ConstantIntegerType($i), $valueType, true);
			}
		}

		return $builder->getArray();
	}

	/**
	 * The greatest number of elements the split can produce, or null when the
	 * limit is unbounded or larger than the constant array builder handles.
	 */
	private function getMaximumElementCount(?Type $limitType): ?int
	{
		if ($limitType === null) {
			return null;
		}

		$max = null;
		foreach ($limitType->getFiniteTypes() as $finiteType) {
			if (!$finiteType instanceof ConstantIntegerType) {
				return null;
			}

			$value = $finiteType->getValue();
			if ($value > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				return null;
			}

			$max = $max === null ? $value : max($max, $value);
		}

		return $max;
	}

}
