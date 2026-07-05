<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
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
use function count;
use function max;

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

	public function __construct(private PhpVersion $phpVersion)
	{
	}

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

		$delimiterType = $scope->getType($args[0]->value);
		$isEmptyString = (new ConstantStringType(''))->isSuperTypeOf($delimiterType);
		if ($isEmptyString->yes()) {
			if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
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

		if (!$this->phpVersion->throwsValueErrorForInternalFunctions() && $isEmptyString->maybe()) {
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
