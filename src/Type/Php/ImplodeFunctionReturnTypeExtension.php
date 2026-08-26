<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function implode;
use function in_array;

#[AutowiredService]
final class ImplodeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'implode',
			'join',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 1) {
			$argType = $scope->getType($args[0]->value);
			if ($argType->isArray()->yes()) {
				return $this->implode($argType, new ConstantStringType(''));
			}
		}

		if (count($args) !== 2) {
			return new StringType();
		}

		$separatorType = $scope->getType($args[0]->value);
		$arrayType = $scope->getType($args[1]->value);

		return $this->implode($arrayType, $separatorType);
	}

	private function implode(Type $arrayType, Type $separatorType): Type
	{
		if (count($arrayType->getConstantArrays()) > 0 && count($separatorType->getConstantStrings()) > 0) {
			$isNonEmpty = $arrayType->isIterableAtLeastOnce()->yes();
			$result = [];
			foreach ($separatorType->getConstantStrings() as $separator) {
				foreach ($arrayType->getConstantArrays() as $constantArray) {
					$constantType = $this->inferConstantType($constantArray, $separator, $isNonEmpty);
					if ($constantType !== null) {
						$result[] = $constantType;
						continue;
					}

					$result = [];
					break 2;
				}
			}

			if (count($result) > 0) {
				return TypeCombinator::union(...$result);
			}
		}

		$accessoryTypes = [];
		$valueTypeAsString = $arrayType->getIterableValueType()->toString();
		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			// The separator only appears between elements, so it can only
			// guarantee a non-empty/non-falsy result when the array has at
			// least two elements. A single-element array drops the separator
			// entirely (e.g. implode(',', ['']) === '').
			$separatorAppears = IntegerRangeType::createAllGreaterThanOrEqualTo(2)->isSuperTypeOf($arrayType->getArraySize())->yes();
			if ($valueTypeAsString->isNonFalsyString()->yes() || ($separatorAppears && $separatorType->isNonFalsyString()->yes())) {
				$accessoryTypes[] = new AccessoryNonFalsyStringType();
			} elseif ($valueTypeAsString->isNonEmptyString()->yes() || ($separatorAppears && $separatorType->isNonEmptyString()->yes())) {
				$accessoryTypes[] = new AccessoryNonEmptyStringType();
			}
		}

		// implode is one of the four functions that can produce literal strings as blessed by the original RFC: wiki.php.net/rfc/is_literal
		if ($arrayType->getIterableValueType()->isLiteralString()->yes() && $separatorType->isLiteralString()->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}
		if ($valueTypeAsString->isLowercaseString()->yes() && $separatorType->isLowercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}
		if ($valueTypeAsString->isUppercaseString()->yes() && $separatorType->isUppercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

	private function inferConstantType(ConstantArrayType $arrayType, ConstantStringType $separatorType, bool $isNonEmpty): ?Type
	{
		// Unsealed extras can append further segments the constant fold
		// can't see, so the exact string result would be unsound. Fall
		// back to the accessory-based result.
		if ($arrayType->isUnsealed()->yes()) {
			return null;
		}

		$sep = $separatorType->getValue();
		$valueTypes = $arrayType->getValueTypes();
		$limit = InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;

		// Build implode results incrementally, processing one key at a time.
		// For optional keys, fork each partial result into with/without variants.
		// This avoids generating 2^N ConstantArrayType objects via getAllArrays().
		/** @var list<list<scalar>> $partials */
		$partials = [[]];

		foreach ($valueTypes as $i => $valueType) {
			$constScalars = $valueType->getConstantScalarValues();
			if (count($constScalars) === 0) {
				return null;
			}

			$isOptional = $arrayType->isOptionalKey($i);
			$newPartials = [];

			foreach ($partials as $partial) {
				if ($isOptional) {
					$newPartials[] = $partial;
				}
				foreach ($constScalars as $scalar) {
					$newPartial = $partial;
					$newPartial[] = $scalar;
					$newPartials[] = $newPartial;
				}
			}

			$partials = $newPartials;
			if (count($partials) > $limit) {
				return null;
			}
		}

		$strings = [];
		foreach ($partials as $partial) {
			if ($partial === [] && $isNonEmpty) {
				continue;
			}
			$strings[] = new ConstantStringType(implode($sep, $partial));
		}

		if ($strings === []) {
			return null;
		}

		return TypeCombinator::union(...$strings);
	}

}
