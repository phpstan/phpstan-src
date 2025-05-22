<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function str_contains;

final class MbConvertEncodingFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'mb_convert_encoding';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);

		$initialReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		$result = TypeCombinator::intersect($initialReturnType, $this->generalizeStringType($argType));
		if ($result instanceof NeverType) {
			$result = $initialReturnType;
		}

		if ($this->phpVersion->throwsValueErrorForInternalFunctions()) {
			if (!isset($functionCall->getArgs()[2])) {
				return TypeCombinator::remove($result, new ConstantBooleanType(false));
			}
			$fromEncodingArgType = $scope->getType($functionCall->getArgs()[2]->value);

			$returnFalseIfCannotDetectEncoding = false;
			if (!$fromEncodingArgType->isArray()->no()) {
				$constantArrays = $fromEncodingArgType->getConstantArrays();
				if (count($constantArrays) > 0) {
					foreach ($constantArrays as $constantArray) {
						if (count($constantArray->getValueTypes()) > 1) {
							$returnFalseIfCannotDetectEncoding = true;
							break;
						}
					}
				} else {
					$returnFalseIfCannotDetectEncoding = true;
				}
			}
			if (!$returnFalseIfCannotDetectEncoding && !$fromEncodingArgType->isString()->no()) {
				$constantStrings = $fromEncodingArgType->getConstantStrings();
				if (count($constantStrings) > 0) {
					foreach ($constantStrings as $constantString) {
						if (str_contains($constantString->getValue(), ',')) {
							$returnFalseIfCannotDetectEncoding = true;
							break;
						}
					}
				} else {
					$returnFalseIfCannotDetectEncoding = true;
				}
			}

			if (!$returnFalseIfCannotDetectEncoding) {
				return TypeCombinator::remove($result, new ConstantBooleanType(false));
			}
		}

		return TypeCombinator::union($result, new ConstantBooleanType(false));
	}

	public function generalizeStringType(Type $type): Type
	{
		if ($type instanceof UnionType) {
			return $type->traverse([$this, 'generalizeStringType']);
		}

		if ($type->isString()->yes()) {
			return new StringType();
		}

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) > 0) {
			$types = [];
			foreach ($constantArrays as $constantArray) {
				$types[] = $constantArray->traverse([$this, 'generalizeStringType']);
			}

			return TypeCombinator::union(...$types);
		}

		if ($type->isArray()->yes()) {
			$newArrayType = new ArrayType($type->getIterableKeyType(), $this->generalizeStringType($type->getIterableValueType()));
			if ($type->isIterableAtLeastOnce()->yes()) {
				$newArrayType = TypeCombinator::intersect($newArrayType, new NonEmptyArrayType());
			}
			if ($type->isList()->yes()) {
				$newArrayType = TypeCombinator::intersect($newArrayType, new AccessoryArrayListType());
			}

			return $newArrayType;
		}

		return $type;
	}

}
