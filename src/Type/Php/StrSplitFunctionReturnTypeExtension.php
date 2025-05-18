<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_is_list;
use function array_map;
use function array_unique;
use function count;
use function in_array;
use function mb_internal_encoding;
use function mb_str_split;
use function str_split;

#[AutowiredService]
final class StrSplitFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	use MbFunctionsReturnTypeExtensionTrait;

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['str_split', 'mb_str_split'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		if (count($functionCall->getArgs()) >= 2) {
			$splitLengthType = $scope->getType($functionCall->getArgs()[1]->value);
		} else {
			$splitLengthType = new ConstantIntegerType(1);
		}

		if ($splitLengthType instanceof ConstantIntegerType) {
			$splitLength = $splitLengthType->getValue();
			if ($splitLength < 1) {
				return $this->phpVersion->throwsValueErrorForInternalFunctions() ? new NeverType() : new ConstantBooleanType(false);
			}
		}

		$encoding = null;
		if ($functionReflection->getName() === 'mb_str_split') {
			if (count($functionCall->getArgs()) >= 3) {
				$strings = $scope->getType($functionCall->getArgs()[2]->value)->getConstantStrings();
				$values = array_unique(array_map(static fn (ConstantStringType $encoding): string => $encoding->getValue(), $strings));

				if (count($values) === 1) {
					$encoding = $values[0];
					if (!$this->isSupportedEncoding($encoding)) {
						return $this->phpVersion->throwsValueErrorForInternalFunctions() ? new NeverType() : new ConstantBooleanType(false);
					}
				}
			} else {
				$encoding = mb_internal_encoding();
			}
		}

		$stringType = $scope->getType($functionCall->getArgs()[0]->value);
		if (
			isset($splitLength)
			&& ($functionReflection->getName() === 'str_split' || $encoding !== null)
		) {
			$constantStrings = $stringType->getConstantStrings();
			if (count($constantStrings) > 0) {
				$results = [];
				foreach ($constantStrings as $constantString) {
					$value = $constantString->getValue();

					if ($encoding === null && $value === '') {
						// Simulate the str_split call with the analysed PHP Version instead of the runtime one.
						$items = $this->phpVersion->strSplitReturnsEmptyArray() ? [] : [''];
					} else {
						$items = $encoding === null
							? str_split($value, $splitLength)
							: @mb_str_split($value, $splitLength, $encoding);
					}

					$results[] = self::createConstantArrayFrom($items, $scope);
				}

				return TypeCombinator::union(...$results);
			}
		}

		$isInputNonEmptyString = $stringType->isNonEmptyString()->yes();

		if ($isInputNonEmptyString || $this->phpVersion->strSplitReturnsEmptyArray()) {
			$returnValueType = TypeCombinator::intersect(new StringType(), new AccessoryNonEmptyStringType());
		} else {
			$returnValueType = new StringType();
		}

		$returnType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $returnValueType), new AccessoryArrayListType());
		if (
			// Non-empty-string will return an array with at least an element
			$isInputNonEmptyString
			// str_split('', 1) returns [''] on old PHP version and [] on new ones
			|| ($functionReflection->getName() === 'str_split' && !$this->phpVersion->strSplitReturnsEmptyArray())
		) {
			$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
		}
		if (
			// Length parameter accepts int<1, max> or throws a ValueError/return false based on PHP Version.
			!$this->phpVersion->throwsValueErrorForInternalFunctions()
			&& !IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($splitLengthType)->yes()
		) {
			$returnType = TypeCombinator::union($returnType, new ConstantBooleanType(false));
		}

		return $returnType;
	}

	/**
	 * @param string[] $constantArray
	 */
	private static function createConstantArrayFrom(array $constantArray, Scope $scope): ConstantArrayType
	{
		$keyTypes = [];
		$valueTypes = [];
		$isList = true;
		$i = 0;

		foreach ($constantArray as $key => $value) {
			$keyType = $scope->getTypeFromValue($key);
			if (!$keyType instanceof ConstantIntegerType) {
				throw new ShouldNotHappenException();
			}
			$keyTypes[] = $keyType;

			$valueTypes[] = $scope->getTypeFromValue($value);

			$isList = $isList && $key === $i;
			$i++;
		}

		return new ConstantArrayType($keyTypes, $valueTypes, $isList ? [$i] : [0], isList: TrinaryLogic::createFromBoolean(array_is_list($constantArray)));
	}

}
