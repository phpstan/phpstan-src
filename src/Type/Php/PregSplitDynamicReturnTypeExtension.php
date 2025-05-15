<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function is_array;
use function is_int;
use function is_numeric;
use function preg_split;
use function strtolower;

final class PregSplitDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private readonly BitwiseFlagHelper $bitwiseFlagAnalyser,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'preg_split';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}
		$patternArg = $args[0];
		$subjectArg = $args[1];
		$limitArg = $args[2] ?? null;
		$capturesOffset = $args[3] ?? null;
		$patternType = $scope->getType($patternArg->value);
		$patternConstantTypes = $patternType->getConstantStrings();
		if (count($patternConstantTypes) > 0) {
			foreach ($patternConstantTypes as $patternConstantType) {
				if ($this->isValidPattern($patternConstantType->getValue()) === false) {
					return new ErrorType();
				}
			}
		}

		$subjectType = $scope->getType($subjectArg->value);
		$subjectConstantTypes = $subjectType->getConstantStrings();

		$limits = [];
		if ($limitArg === null) {
			$limits = [-1];
		} else {
			$limitType = $scope->getType($limitArg->value);
			if (!$this->isIntOrStringValue($limitType)) {
				return new ErrorType();
			}
			foreach ($limitType->getConstantScalarValues() as $limit) {
				if (!is_int($limit) && !is_numeric($limit)) {
					return new ErrorType();
				}
				$limits[] = $limit;
			}
		}

		$flags = [];
		if ($capturesOffset === null) {
			$flags = [0];
		} else {
			$flagType = $scope->getType($capturesOffset->value);
			if (!$this->isIntOrStringValue($flagType)) {
				return new ErrorType();
			}
			foreach ($flagType->getConstantScalarValues() as $flag) {
				if (!is_int($flag) && !is_numeric($flag)) {
					return new ErrorType();
				}
				$flags[] = $flag;
			}
		}

		if ($this->isPatternOrSubjectEmpty($patternConstantTypes, $subjectConstantTypes)) {
			if ($capturesOffset !== null
				&& $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($capturesOffset->value, $scope, 'PREG_SPLIT_NO_EMPTY')->yes()) {
				$returnStringType = TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				);
			} else {
				$returnStringType = new StringType();
			}

			$arrayTypeBuilder = ConstantArrayTypeBuilder::createEmpty();
			$arrayTypeBuilder->setOffsetValueType(
				new ConstantIntegerType(0),
				$returnStringType,
			);
			$arrayTypeBuilder->setOffsetValueType(
				new ConstantIntegerType(1),
				IntegerRangeType::fromInterval(0, null),
			);
			$capturedArrayType = $arrayTypeBuilder->getArray();

			$returnInternalValueType = $returnStringType;
			if ($capturesOffset !== null) {
				$flagState = $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($capturesOffset->value, $scope, 'PREG_SPLIT_OFFSET_CAPTURE');
				if ($flagState->yes()) {
					$capturedArrayListType = TypeCombinator::intersect(
						new ArrayType(new IntegerType(), $capturedArrayType),
						new AccessoryArrayListType(),
					);

					if ($subjectType->isNonEmptyString()->yes()) {
						$capturedArrayListType = TypeCombinator::intersect($capturedArrayListType, new NonEmptyArrayType());
					}
					return TypeCombinator::union($capturedArrayListType, new ConstantBooleanType(false));
				}
				if ($flagState->maybe()) {
					$returnInternalValueType = TypeCombinator::union(new StringType(), $capturedArrayType);
				}
			}

			$returnListType = TypeCombinator::intersect(new ArrayType(new MixedType(), $returnInternalValueType), new AccessoryArrayListType());
			if ($subjectType->isNonEmptyString()->yes()) {
				$returnListType = TypeCombinator::intersect(
					$returnListType,
					new NonEmptyArrayType(),
				);
			}

			return TypeCombinator::union($returnListType, new ConstantBooleanType(false));
		}

		$resultTypes = [];
		foreach ($patternConstantTypes as $patternConstantType) {
			foreach ($subjectConstantTypes as $subjectConstantType) {
				foreach ($limits as $limit) {
					foreach ($flags as $flag) {
						$result = @preg_split($patternConstantType->getValue(), $subjectConstantType->getValue(), (int) $limit, (int) $flag);
						if ($result === false) {
							return new ErrorType();
						}
						$constantArray = ConstantArrayTypeBuilder::createEmpty();
						foreach ($result as $key => $value) {
							if (is_array($value)) {
								$valueConstantArray = ConstantArrayTypeBuilder::createEmpty();
								$valueConstantArray->setOffsetValueType(new ConstantIntegerType(0), new ConstantStringType($value[0]));
								$valueConstantArray->setOffsetValueType(new ConstantIntegerType(1), new ConstantIntegerType($value[1]));
								$returnInternalValueType = $valueConstantArray->getArray();
							} else {
								$returnInternalValueType = new ConstantStringType($value);
							}
							$constantArray->setOffsetValueType(new ConstantIntegerType($key), $returnInternalValueType);
						}

						$resultTypes[] = $constantArray->getArray();
					}
				}
			}
		}
		$resultTypes[] = new ConstantBooleanType(false);
		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * @param ConstantStringType[] $patternConstantArray
	 * @param ConstantStringType[] $subjectConstantArray
	 */
	private function isPatternOrSubjectEmpty(array $patternConstantArray, array $subjectConstantArray): bool
	{
		return count($patternConstantArray) === 0 || count($subjectConstantArray) === 0;
	}

	private function isValidPattern(string $pattern): bool
	{
		try {
			Strings::match('', $pattern);
		} catch (RegexpException) {
			return false;
		}
		return true;
	}

	private function isIntOrStringValue(Type $type): bool
	{
		return (new UnionType([new IntegerType(), new StringType()]))->isSuperTypeOf($type)->yes();
	}

}
