<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function count;
use function in_array;

final class ReplaceFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const FUNCTIONS_SUBJECT_POSITION = [
		'preg_replace' => 2,
		'preg_replace_callback' => 2,
		'preg_replace_callback_array' => 1,
		'str_replace' => 2,
		'str_ireplace' => 2,
		'substr_replace' => 0,
		'strtr' => 0,
	];

	private const FUNCTIONS_REPLACE_POSITION = [
		'preg_replace' => 1,
		'str_replace' => 1,
		'str_ireplace' => 1,
		'substr_replace' => 1,
		'strtr' => 2,
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), self::FUNCTIONS_SUBJECT_POSITION);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$type = $this->getPreliminarilyResolvedTypeFromFunctionCall($functionReflection, $functionCall, $scope);

		if ($this->canReturnNull($functionReflection, $functionCall, $scope)) {
			$type = TypeCombinator::addNull($type);
		}

		return $type;
	}

	private function getPreliminarilyResolvedTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$subjectArgumentType = $this->getSubjectType($functionReflection, $functionCall, $scope);
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		if ($subjectArgumentType === null) {
			return $defaultReturnType;
		}

		if ($subjectArgumentType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		if (array_key_exists($functionReflection->getName(), self::FUNCTIONS_REPLACE_POSITION)) {
			$replaceArgumentPosition = self::FUNCTIONS_REPLACE_POSITION[$functionReflection->getName()];

			if (count($functionCall->getArgs()) > $replaceArgumentPosition) {
				$replaceArgumentType = $scope->getType($functionCall->getArgs()[$replaceArgumentPosition]->value);
				if ($replaceArgumentType->isArray()->yes()) {
					$replaceArgumentType = $replaceArgumentType->getIterableValueType();
				}

				$accessories = [];
				if ($subjectArgumentType->isNonFalsyString()->yes() && $replaceArgumentType->isNonFalsyString()->yes()) {
					$accessories[] = new AccessoryNonFalsyStringType();
				} elseif ($subjectArgumentType->isNonEmptyString()->yes() && $replaceArgumentType->isNonEmptyString()->yes()) {
					$accessories[] = new AccessoryNonEmptyStringType();
				}

				if ($subjectArgumentType->isLowercaseString()->yes() && $replaceArgumentType->isLowercaseString()->yes()) {
					$accessories[] = new AccessoryLowercaseStringType();
				}

				if ($subjectArgumentType->isUppercaseString()->yes() && $replaceArgumentType->isUppercaseString()->yes()) {
					$accessories[] = new AccessoryUppercaseStringType();
				}

				if (count($accessories) > 0) {
					$accessories[] = new StringType();
					return new IntersectionType($accessories);
				}
			}
		}

		$isStringSuperType = $subjectArgumentType->isString();
		$isArraySuperType = $subjectArgumentType->isArray();
		$compareSuperTypes = $isStringSuperType->compareTo($isArraySuperType);
		if ($compareSuperTypes === $isStringSuperType) {
			return new StringType();
		} elseif ($compareSuperTypes === $isArraySuperType) {
			$subjectArrays = $subjectArgumentType->getArrays();
			if (count($subjectArrays) > 0) {
				$result = [];
				foreach ($subjectArrays as $arrayType) {
					$constantArrays = $arrayType->getConstantArrays();

					if (
						$constantArrays !== []
						&& in_array($functionReflection->getName(), ['preg_replace', 'preg_replace_callback', 'preg_replace_callback_array'], true)
					) {
						foreach ($constantArrays as $constantArray) {
							$generalizedArray = $constantArray->generalizeValues();

							$builder = ConstantArrayTypeBuilder::createEmpty();
							// turn all keys optional
							foreach ($constantArray->getKeyTypes() as $keyType) {
								$builder->setOffsetValueType($keyType, $generalizedArray->getOffsetValueType($keyType), true);
							}
							$result[] = $builder->getArray();
						}

						continue;
					}

					$result[] = $arrayType->generalizeValues();
				}

				return TypeCombinator::union(...$result);
			}
			return $subjectArgumentType;
		}

		return $defaultReturnType;
	}

	private function getSubjectType(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$argumentPosition = self::FUNCTIONS_SUBJECT_POSITION[$functionReflection->getName()];
		if (count($functionCall->getArgs()) <= $argumentPosition) {
			return null;
		}
		return $scope->getType($functionCall->getArgs()[$argumentPosition]->value);
	}

	private function canReturnNull(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): bool
	{
		if (
			in_array($functionReflection->getName(), ['preg_replace', 'preg_replace_callback', 'preg_replace_callback_array'], true)
			&& count($functionCall->getArgs()) > 0
		) {
			$subjectArgumentType = $this->getSubjectType($functionReflection, $functionCall, $scope);

			if (
				$subjectArgumentType !== null
				&& $subjectArgumentType->isArray()->yes()
			) {
				return false;
			}
		}

		$possibleTypes = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		// resolve conditional return types
		$possibleTypes = TypeUtils::resolveLateResolvableTypes($possibleTypes);

		return TypeCombinator::containsNull($possibleTypes);
	}

}
