<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_map;
use function count;
use function strtolower;
use function strtoupper;
use const CASE_LOWER;
use const CASE_UPPER;

final class ArrayChangeKeyCaseFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_change_key_case';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		if (!isset($functionCall->getArgs()[1])) {
			$case = CASE_LOWER;
		} else {
			$caseType = $scope->getType($functionCall->getArgs()[1]->value);
			$scalarValues = $caseType->getConstantScalarValues();
			if (count($scalarValues) === 1) {
				$case = (int) $scalarValues[0];
			} else {
				$case = null;
			}
		}

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$arrayTypes = [];
			foreach ($constantArrays as $constantArray) {
				$newConstantArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
				$valueTypes = $constantArray->getValueTypes();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$valueType = $valueTypes[$i];

					$constantStrings = $keyType->getConstantStrings();
					if (count($constantStrings) > 0) {
						$keyType = TypeCombinator::union(
							...array_map(
								fn (ConstantStringType $type): Type => $this->mapConstantString($type, $case),
								$constantStrings,
							),
						);
					}

					$newConstantArrayBuilder->setOffsetValueType(
						$keyType,
						$valueType,
						$constantArray->isOptionalKey($i),
					);
				}
				$newConstantArrayType = $newConstantArrayBuilder->getArray();
				if ($constantArray->isList()->yes()) {
					$newConstantArrayType = AccessoryArrayListType::intersectWith($newConstantArrayType);
				}
				$arrayTypes[] = $newConstantArrayType;
			}

			$newArrayType = TypeCombinator::union(...$arrayTypes);
		} else {
			$keysType = $arrayType->getIterableKeyType();

			$keysType = TypeTraverser::map($keysType, function (Type $type, callable $traverse) use ($case): Type {
				if ($type instanceof UnionType) {
					return $traverse($type);
				}

				$constantStrings = $type->getConstantStrings();
				if (count($constantStrings) > 0) {
					return TypeCombinator::union(
						...array_map(
							fn (ConstantStringType $type): Type => $this->mapConstantString($type, $case),
							$constantStrings,
						),
					);
				}

				if ($type->isString()->yes()) {
					$types = [new StringType()];
					if ($type->isNonFalsyString()->yes()) {
						$types[] = new AccessoryNonFalsyStringType();
					} elseif ($type->isNonEmptyString()->yes()) {
						$types[] = new AccessoryNonEmptyStringType();
					}
					if ($type->isNumericString()->yes()) {
						$types[] = new AccessoryNumericStringType();
					}
					if ($case === CASE_LOWER) {
						$types[] = new AccessoryLowercaseStringType();
					} elseif ($case === CASE_UPPER) {
						$types[] = new AccessoryUppercaseStringType();
					}

					return TypeCombinator::intersect(...$types);
				}

				return $type;
			});

			$newArrayType = TypeCombinator::intersect(new ArrayType(
				$keysType,
				$arrayType->getIterableValueType(),
			), ...TypeUtils::getAccessoryTypes($arrayType));
		}

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$newArrayType = TypeCombinator::intersect($newArrayType, new NonEmptyArrayType());
		}

		return $newArrayType;
	}

	private function mapConstantString(ConstantStringType $type, ?int $case): Type
	{
		if ($case === CASE_LOWER) {
			return new ConstantStringType(strtolower($type->getValue()));
		} elseif ($case === CASE_UPPER) {
			return new ConstantStringType(strtoupper($type->getValue()));
		}

		return TypeCombinator::union(
			new ConstantStringType(strtolower($type->getValue())),
			new ConstantStringType(strtoupper($type->getValue())),
		);
	}

}
