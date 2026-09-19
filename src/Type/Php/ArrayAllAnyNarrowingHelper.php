<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

/**
 * Builds the narrowed array type for `array_all` (truthy) and `array_any`
 * (falsey): every element is required to satisfy (array_all) or fail
 * (array_any) the predicate, so element value/key types are refined
 * accordingly.
 *
 * When narrowing rejects a general element, the member becomes `array{}`
 * (an empty array still makes `array_all` true / `array_any` false) rather than
 * `never` - the caller's scope intersection turns a genuinely non-empty array
 * into `never` on its own.
 */
#[AutowiredService]
final class ArrayAllAnyNarrowingHelper
{

	public function __construct(
		private ArrayPredicateCallbackResolver $predicateCallbackResolver,
	)
	{
	}

	/**
	 * @param bool $truthy true narrows elements by the predicate being truthy
	 *                     (array_all), false by the predicate being falsey
	 *                     (array_any).
	 */
	public function narrowArrayType(MutatingScope $scope, Type $arrayType, ArrayCallbackPredicate $predicate, bool $truthy): ?Type
	{
		if ($predicate->getExpr() === null) {
			return null;
		}

		$arrays = $arrayType->getArrays();
		if (count($arrays) === 0) {
			return null;
		}

		$results = [];
		foreach ($arrays as $array) {
			$constantArrays = $array->getConstantArrays();
			if (count($constantArrays) > 0) {
				foreach ($constantArrays as $constantArray) {
					$results[] = $this->narrowConstantArray($scope, $constantArray, $predicate, $truthy);
				}
				continue;
			}

			[$newKey, $newValue, $rejected] = $this->narrowElement($scope, $array->getIterableKeyType(), $array->getIterableValueType(), $predicate, $truthy);
			if ($rejected) {
				$results[] = new ConstantArrayType([], []);
				continue;
			}

			$results[] = new ArrayType($newKey, $newValue);
		}

		return TypeCombinator::union(...$results);
	}

	private function narrowConstantArray(MutatingScope $scope, ConstantArrayType $constantArray, ArrayCallbackPredicate $predicate, bool $truthy): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$optionalKeys = $constantArray->getOptionalKeys();

		foreach ($constantArray->getKeyTypes() as $i => $keyType) {
			$itemType = $constantArray->getValueTypes()[$i];
			$optional = in_array($i, $optionalKeys, true);

			[, $newValue, $rejected] = $this->narrowElement($scope, $keyType, $itemType, $predicate, $truthy);
			if ($rejected) {
				if ($optional) {
					// The predicate rejects this element, so satisfying the
					// whole array requires the optional offset to be absent.
					continue;
				}

				// A required offset that cannot satisfy the predicate makes
				// the whole shape impossible.
				return new NeverType();
			}

			$builder->setOffsetValueType($keyType, $newValue, $optional);
		}

		if ($constantArray->isUnsealed()->yes()) {
			$unsealedTypes = $constantArray->getUnsealedTypes();
			if ($unsealedTypes !== null) {
				[$newKey, $newValue, $rejected] = $this->narrowElement($scope, $unsealedTypes[0], $unsealedTypes[1], $predicate, $truthy);
				if (!$rejected) {
					$builder->makeUnsealed($newKey, $newValue);
				}
			}
		}

		return $builder->getArray();
	}

	/**
	 * @return array{Type, Type, bool} the narrowed key and value, plus whether
	 *                                 the element cannot satisfy the predicate.
	 */
	private function narrowElement(MutatingScope $scope, Type $keyType, Type $itemType, ArrayCallbackPredicate $predicate, bool $truthy): array
	{
		[$scope, $itemVarName, $keyVarName] = $this->predicateCallbackResolver->assignPredicateVariables($scope, $predicate, $itemType, $keyType);

		$expr = $predicate->getExpr();
		if ($expr === null) {
			throw new ShouldNotHappenException();
		}

		$booleanResult = $scope->getType($expr)->toBoolean();
		$impossible = $truthy ? $booleanResult->isFalse()->yes() : $booleanResult->isTrue()->yes();
		if ($impossible) {
			return [new NeverType(), new NeverType(), true];
		}

		$narrowedScope = $truthy ? $scope->filterByTruthyValue($expr) : $scope->filterByFalseyValue($expr);
		$newKey = $keyVarName !== null ? $narrowedScope->getVariableType($keyVarName) : $keyType;
		$newValue = $itemVarName !== null ? $narrowedScope->getVariableType($itemVarName) : $itemType;

		$rejected = $newKey instanceof NeverType || $newValue instanceof NeverType;

		return [$newKey, $newValue, $rejected];
	}

}
