<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
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

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		if (count($functionCall->getArgs()) < 1) {
			return $defaultReturnType;
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
				$strings = TypeUtils::getConstantStrings($scope->getType($functionCall->getArgs()[2]->value));
				$values = array_unique(array_map(static fn (ConstantStringType $encoding): string => $encoding->getValue(), $strings));

				if (count($values) !== 1) {
					return $defaultReturnType;
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
			return $defaultReturnType;
		}

		$stringType = $scope->getType($functionCall->getArgs()[0]->value);

		return TypeTraverser::map($stringType, static function (Type $type, callable $traverse) use ($encoding, $splitLength, $scope): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}

			if (!$type instanceof ConstantStringType) {
				return TypeCombinator::intersect(
					new ArrayType(new IntegerType(), new StringType()),
					new NonEmptyArrayType(),
				);
			}

			$stringValue = $type->getValue();

			$items = $encoding !== null
				? mb_str_split($stringValue, $splitLength, $encoding)
				: str_split($stringValue, $splitLength);
			if ($items === false) {
				throw new ShouldNotHappenException();
			}

			return self::createConstantArrayFrom($items, $scope);
		});
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

		return new ConstantArrayType($keyTypes, $valueTypes, $isList ? [$i] : [0]);
	}

}
