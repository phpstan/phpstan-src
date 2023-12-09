<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
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
			if ($splitLengthType instanceof ConstantIntegerType) {
				$splitLength = $splitLengthType->getValue();
				if ($splitLength < 1) {
					return new ConstantBooleanType(false);
				}
			}
		} else {
			$splitLength = 1;
		}

		$encoding = null;
		if ($functionReflection->getName() === 'mb_str_split') {
			if (count($functionCall->getArgs()) >= 3) {
				$strings = $scope->getType($functionCall->getArgs()[2]->value)->getConstantStrings();
				$values = array_unique(array_map(static fn (ConstantStringType $encoding): string => $encoding->getValue(), $strings));

				if (count($values) !== 1) {
					return null;
				}

				$encoding = $values[0];
				if (!$this->isSupportedEncoding($encoding)) {
					return new ConstantBooleanType(false);
				}
			} else {
				$encoding = mb_internal_encoding();
			}
		}

		if (!isset($splitLength)) {
			return null;
		}

		$stringType = $scope->getType($functionCall->getArgs()[0]->value);

		$constantStrings = $stringType->getConstantStrings();
		if (count($constantStrings) > 0) {
			$results = [];
			foreach ($constantStrings as $constantString) {
				$items = $encoding === null
					? str_split($constantString->getValue(), $splitLength)
					: @mb_str_split($constantString->getValue(), $splitLength, $encoding);
				if ($items === false) {
					throw new ShouldNotHappenException();
				}

				$results[] = self::createConstantArrayFrom($items, $scope);
			}

			return TypeCombinator::union(...$results);
		}

		$returnType = AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new StringType()));

		return $encoding === null && !$this->phpVersion->strSplitReturnsEmptyArray()
			? TypeCombinator::intersect($returnType, new NonEmptyArrayType())
			: $returnType;
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

		return new ConstantArrayType($keyTypes, $valueTypes, $isList ? [$i] : [0], [], TrinaryLogic::createFromBoolean(array_is_list($constantArray)));
	}

}
