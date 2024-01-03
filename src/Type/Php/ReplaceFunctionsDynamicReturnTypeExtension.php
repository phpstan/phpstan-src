<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function count;

class ReplaceFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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

		$possibleTypes = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();
		// resolve conditional return types
		$possibleTypes = TypeUtils::resolveLateResolvableTypes($possibleTypes);

		if (TypeCombinator::containsNull($possibleTypes)) {
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
		$argumentPosition = self::FUNCTIONS_SUBJECT_POSITION[$functionReflection->getName()];
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		if (count($functionCall->getArgs()) <= $argumentPosition) {
			return $defaultReturnType;
		}

		$subjectArgumentType = $scope->getType($functionCall->getArgs()[$argumentPosition]->value);
		if ($subjectArgumentType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		if ($subjectArgumentType->isNonEmptyString()->yes() && array_key_exists($functionReflection->getName(), self::FUNCTIONS_REPLACE_POSITION)) {
			$replaceArgumentPosition = self::FUNCTIONS_REPLACE_POSITION[$functionReflection->getName()];

			if (count($functionCall->getArgs()) > $replaceArgumentPosition) {
				$replaceArgumentType = $scope->getType($functionCall->getArgs()[$replaceArgumentPosition]->value);

				if ($subjectArgumentType->isNonFalsyString()->yes() && $replaceArgumentType->isNonFalsyString()->yes()) {
					return new IntersectionType([new StringType(), new AccessoryNonFalsyStringType()]);
				}
				if ($replaceArgumentType->isNonEmptyString()->yes()) {
					return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
				}
			}
		}

		$isStringSuperType = $subjectArgumentType->isString();
		$isArraySuperType = $subjectArgumentType->isArray();
		$compareSuperTypes = $isStringSuperType->compareTo($isArraySuperType);
		if ($compareSuperTypes === $isStringSuperType) {
			return new StringType();
		} elseif ($compareSuperTypes === $isArraySuperType) {
			if (count($subjectArgumentType->getArrays()) > 0) {
				$result = [];
				foreach ($subjectArgumentType->getArrays() as $arrayType) {
					$result[] = $arrayType->generalizeValues();
				}

				return TypeCombinator::union(...$result);
			}
			return $subjectArgumentType;
		}

		return $defaultReturnType;
	}

}
