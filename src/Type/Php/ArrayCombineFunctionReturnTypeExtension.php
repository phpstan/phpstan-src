<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function is_int;
use function is_string;

#[AutowiredService]
final class ArrayCombineFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_combine';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$firstArg = $functionCall->getArgs()[0]->value;
		$secondArg = $functionCall->getArgs()[1]->value;

		$keysParamType = $scope->getType($firstArg);
		$valuesParamType = $scope->getType($secondArg);

		$constantKeysArrays = $keysParamType->getConstantArrays();
		$constantValuesArrays = $valuesParamType->getConstantArrays();
		if (
			$constantKeysArrays !== []
			&& $constantValuesArrays !== []
			&& count($constantKeysArrays) === count($constantValuesArrays)
		) {
			$results = [];
			foreach ($constantKeysArrays as $k => $constantKeysArray) {
				$constantValueArrays = $constantValuesArrays[$k];

				$keyTypes = $constantKeysArray->getValueTypes();
				$valueTypes = $constantValueArrays->getValueTypes();

				if (count($keyTypes) !== count($valueTypes)) {
					if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
						return new NeverType();
					}
					return new ConstantBooleanType(false);
				}

				$keyTypes = $this->sanitizeConstantArrayKeyTypes($keyTypes);
				if ($keyTypes === null) {
					continue;
				}

				$builder = ConstantArrayTypeBuilder::createEmpty();
				foreach ($keyTypes as $i => $keyType) {
					$valueType = $valueTypes[$i];
					$builder->setOffsetValueType($keyType, $valueType);
				}

				$results[] = $builder->getArray();
			}

			if ($results !== []) {
				return TypeCombinator::union(...$results);
			}
		}

		if ($keysParamType->isArray()->yes()) {
			$itemType = $keysParamType->getIterableValueType();

			if ($itemType->isInteger()->no()) {
				if ($itemType->toString() instanceof ErrorType) {
					return new NeverType();
				}

				$keyType = $itemType->toString();
			} else {
				$keyType = $itemType;
			}
		} else {
			$keyType = new MixedType();
		}

		$arrayType = new ArrayType(
			$keyType,
			$valuesParamType->isArray()->yes() ? $valuesParamType->getIterableValueType() : new MixedType(),
		);

		if ($keysParamType->isIterableAtLeastOnce()->yes() && $valuesParamType->isIterableAtLeastOnce()->yes()) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
			return $arrayType;
		}

		if ($firstArg instanceof Variable && $secondArg instanceof Variable && $firstArg->name === $secondArg->name) {
			return $arrayType;
		}

		return new UnionType([$arrayType, new ConstantBooleanType(false)]);
	}

	/**
	 * @param array<int, Type> $types
	 *
	 * @return list<ConstantScalarType>|null
	 */
	private function sanitizeConstantArrayKeyTypes(array $types): ?array
	{
		$sanitizedTypes = [];

		foreach ($types as $type) {
			if ($type->isInteger()->no() && ! $type->toString() instanceof ErrorType) {
				$type = $type->toString();
			}

			$scalars = $type->getConstantScalarTypes();
			if (count($scalars) === 0) {
				return null;
			}

			foreach ($scalars as $scalar) {
				$value = $scalar->getValue();
				if (!is_int($value) && !is_string($value)) {
					return null;
				}

				$sanitizedTypes[] = $scalar;
			}
		}

		return $sanitizedTypes;
	}

}
