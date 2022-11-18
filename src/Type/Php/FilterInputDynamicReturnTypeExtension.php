<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

class FilterInputDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const INPUT_TYPE_VARIABLE_NAME_MAP = [
		'INPUT_GET' => '_GET',
		'INPUT_POST' => '_POST',
		'INPUT_COOKIE' => '_COOKIE',
		'INPUT_SERVER' => '_SERVER',
		'INPUT_ENV' => '_ENV',
	];

	public function __construct(private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'filter_input';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$inputVariable = $this->determineInputVariable($functionCall->getArgs()[0]);
		$varNameType = $scope->getType($functionCall->getArgs()[1]->value);
		if ($inputVariable === null || !$varNameType->isString()->yes()) {
			return null;
		}

		$inputVariableType = $scope->getType($inputVariable);
		$inputHasOffsetValue = $inputVariableType->hasOffsetValueType($varNameType);
		$flagsType = isset($functionCall->getArgs()[3]) ? $scope->getType($functionCall->getArgs()[3]->value) : null;
		if ($inputHasOffsetValue->no()) {
			return $this->determineNonExistentOffsetReturnType($flagsType);
		}

		$filterType = isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null;
		$filteredType = $this->filterFunctionReturnTypeHelper->getTypeFromFunctionCall(
			$inputVariableType->getOffsetValueType($varNameType),
			$filterType,
			$flagsType,
		);

		return !$inputVariableType->hasOffsetValueType($varNameType)->yes()
			? TypeCombinator::union($filteredType, $this->determineNonExistentOffsetReturnType($flagsType))
			: $filteredType;
	}

	private function determineInputVariable(Arg $type): ?Variable
	{
		if (!$type->value instanceof ConstFetch) {
			return null;
		}

		$variableName = self::INPUT_TYPE_VARIABLE_NAME_MAP[(string) $type->value->name] ?? null;
		return $variableName === null ? null : new Variable($variableName);
	}

	private function determineNonExistentOffsetReturnType(?Type $flagsType): Type
	{
		return $flagsType === null || !$this->filterFunctionReturnTypeHelper->hasConstantFlag('FILTER_NULL_ON_FAILURE', $flagsType)
			? new NullType()
			: new ConstantBooleanType(false);
	}

}
