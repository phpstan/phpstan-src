<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
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
use function in_array;

#[AutowiredService]
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
	): ?Type
	{
		$type = $this->getPreliminarilyResolvedTypeFromFunctionCall($functionReflection, $functionCall, $scope);

		if ($type !== null && $this->canReturnNull($functionReflection, $functionCall, $scope)) {
			$type = TypeCombinator::addNull($type);
		}

		return $type;
	}

	private function getPreliminarilyResolvedTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$subjectArgumentType = $this->getSubjectType($functionReflection, $functionCall, $scope);
		$args = $functionCall->getArgs();

		if ($subjectArgumentType === null) {
			return null;
		}

		if ($subjectArgumentType instanceof MixedType) {
			$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$args,
				$functionReflection->getVariants(),
			)->getReturnType();

			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$replaceArgumentType = null;
		if (array_key_exists($functionReflection->getName(), self::FUNCTIONS_REPLACE_POSITION)) {
			$replaceArgumentPosition = self::FUNCTIONS_REPLACE_POSITION[$functionReflection->getName()];

			if (count($args) > $replaceArgumentPosition) {
				$replaceArgumentType = $scope->getType($args[$replaceArgumentPosition]->value);
				if ($replaceArgumentType->isArray()->yes()) {
					$replaceArgumentType = $replaceArgumentType->getIterableValueType();
				}
			} elseif ($functionReflection->getName() === 'strtr' && isset($functionCall->getArgs()[1])) {
				// `strtr` has two signatures: `strtr($string1, $string2, $string3)` and `strtr($string1, $array)`
				$secondArgumentType = TypeCombinator::intersect(
					new ArrayType(new MixedType(), new MixedType()),
					$scope->getType($functionCall->getArgs()[1]->value),
				);
				$replaceArgumentType = $secondArgumentType->getIterableValueType();
			}
		}

		$result = [];

		if ($subjectArgumentType->isString()->yes()) {
			$stringArgumentType = $subjectArgumentType;
		} else {
			$stringArgumentType = TypeCombinator::intersect(new StringType(), $subjectArgumentType);
		}
		if ($stringArgumentType->isString()->yes()) {
			$result[] = $this->getReplaceType($stringArgumentType, $replaceArgumentType);
		}

		if ($subjectArgumentType->isArray()->yes()) {
			$arrayArgumentType = $subjectArgumentType;
		} else {
			$arrayArgumentType = TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), $subjectArgumentType);
		}
		if ($arrayArgumentType->isArray()->yes()) {
			$keyShouldBeOptional = in_array(
				$functionReflection->getName(),
				['preg_replace', 'preg_replace_callback', 'preg_replace_callback_array'],
				true,
			);

			$mapped = $arrayArgumentType->mapValueType(
				fn (Type $value): Type => $this->getReplaceType($value, $replaceArgumentType),
			);
			if ($keyShouldBeOptional) {
				$mapped = $mapped->makeAllArrayKeysOptional();
			}

			$result[] = $mapped;
		}

		return TypeCombinator::union(...$result);
	}

	private function getReplaceType(
		Type $subjectArgumentType,
		?Type $replaceArgumentType,
	): Type
	{
		if ($replaceArgumentType === null) {
			return new StringType();
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

		return new StringType();
	}

	private function getSubjectType(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (!array_key_exists($functionReflection->getName(), self::FUNCTIONS_SUBJECT_POSITION)) {
			throw new ShouldNotHappenException();
		}

		$argumentPosition = self::FUNCTIONS_SUBJECT_POSITION[$functionReflection->getName()];
		$args = $functionCall->getArgs();
		if (count($args) <= $argumentPosition) {
			return null;
		}
		return $scope->getType($args[$argumentPosition]->value);
	}

	private function canReturnNull(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): bool
	{
		$args = $functionCall->getArgs();
		if (
			in_array($functionReflection->getName(), ['preg_replace', 'preg_replace_callback', 'preg_replace_callback_array'], true)
			&& count($args) > 0
		) {
			$subjectArgumentType = $this->getSubjectType($functionReflection, $functionCall, $scope);

			if (
				$subjectArgumentType !== null
				&& $subjectArgumentType->isArray()->yes()
			) {
				return false;
			}

			return true;
		}

		return false;
	}

}
