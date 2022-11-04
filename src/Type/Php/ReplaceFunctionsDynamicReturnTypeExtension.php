<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\ArrayType;
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

	/** @var array<string, int> */
	private array $functionsSubjectPosition = [
		'preg_replace' => 2,
		'preg_replace_callback' => 2,
		'preg_replace_callback_array' => 1,
		'str_replace' => 2,
		'str_ireplace' => 2,
		'substr_replace' => 0,
		'strtr' => 0,
	];

	/** @var array<string, int> */
	private array $functionsReplacePosition = [
		'preg_replace' => 1,
		'str_replace' => 1,
		'str_ireplace' => 1,
		'substr_replace' => 1,
		'strtr' => 2,
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), $this->functionsSubjectPosition);
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
		$argumentPosition = $this->functionsSubjectPosition[$functionReflection->getName()];
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

		if ($subjectArgumentType->isNonEmptyString()->yes() && array_key_exists($functionReflection->getName(), $this->functionsReplacePosition)) {
			$replaceArgumentPosition = $this->functionsReplacePosition[$functionReflection->getName()];

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
			if ($subjectArgumentType instanceof ArrayType) {
				return $subjectArgumentType->generalizeValues();
			}
			return $subjectArgumentType;
		}

		return $defaultReturnType;
	}

}
