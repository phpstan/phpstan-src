<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
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
use function array_filter;
use function count;
use function strtolower;
use function strtoupper;
use const CASE_LOWER;

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
				$case = $scalarValues[0];
			} else {
				$case = null;
			}
		}

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$arrayTypes = [];
			foreach ($constantArrays as $constantArray) {
				$newConstantArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$valueType = $constantArray->getOffsetValueType($keyType);
					if ($keyType->isString()->yes()) {
						if (!isset($case)) {
							$keyType = TypeCombinator::union(
								new ConstantStringType(strtolower((string) $keyType->getValue())),
								new ConstantStringType(strtoupper((string) $keyType->getValue())),
							);
						} elseif ($case === CASE_LOWER) {
							$keyType = new ConstantStringType(strtolower((string) $keyType->getValue()));
						} else {
							$keyType = new ConstantStringType(strtoupper((string) $keyType->getValue()));
						}
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

			$keysType = TypeTraverser::map($keysType, static function (Type $type, callable $traverse) use ($case): Type {
				if ($type instanceof UnionType) {
					return $traverse($type);
				}

				if ($type->isString()->yes()) {
					if ($case === CASE_LOWER) {
						return TypeCombinator::intersect($type, new AccessoryLowercaseStringType());
					} elseif ($type->isLowercaseString()->yes()) {
						return TypeCombinator::intersect(
							new StringType(),
							...array_filter(
								TypeUtils::getAccessoryTypes($type),
								static fn (Type $accessory): bool => !$accessory instanceof AccessoryLowercaseStringType,
							),
						);
					}
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

}
