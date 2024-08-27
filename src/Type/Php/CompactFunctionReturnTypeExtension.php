<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function array_merge;
use function count;

final class CompactFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private bool $checkMaybeUndefinedVariables)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'compact';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return null;
		}

		if ($scope->canAnyVariableExist() && !$this->checkMaybeUndefinedVariables) {
			return null;
		}

		$array = ConstantArrayTypeBuilder::createEmpty();
		foreach ($functionCall->getArgs() as $arg) {
			$type = $scope->getType($arg->value);
			$constantStrings = $this->findConstantStrings($type);
			if ($constantStrings === null) {
				return null;
			}
			foreach ($constantStrings as $constantString) {
				$has = $scope->hasVariableType($constantString->getValue());
				if ($has->no()) {
					continue;
				}

				$array->setOffsetValueType($constantString, $scope->getVariableType($constantString->getValue()), $has->maybe());
			}
		}

		return $array->getArray();
	}

	/**
	 * @return array<int, ConstantStringType>|null
	 */
	private function findConstantStrings(Type $type): ?array
	{
		if ($type instanceof ConstantStringType) {
			return [$type];
		}

		if ($type instanceof ConstantArrayType) {
			$result = [];
			foreach ($type->getValueTypes() as $valueType) {
				$constantStrings = $this->findConstantStrings($valueType);
				if ($constantStrings === null) {
					return null;
				}

				$result = array_merge($result, $constantStrings);
			}

			return $result;
		}

		return null;
	}

}
