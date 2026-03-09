<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Param;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_find;
use function count;
use function strtolower;

#[AutowiredService]
final class ArrayAllFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'array_all'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		if (!$context->true() || count($args) < 2) {
			return new SpecifiedTypes();
		}

		$array = $args[0]->value;
		$arrayArgType = $scope->getType($array);
		$arrayTypes = $arrayArgType->getArrays();

		if (count($arrayTypes) === 0) {
			return new SpecifiedTypes();
		}

		$callable = $args[1]->value;

		if ($callable instanceof Expr\ArrowFunction) {

			$callableParms = $callable->params;
			$specifiedTypesInFuncCall = $this->typeSpecifier->specifyTypesInCondition($scope, $callable->expr, $context)->getSureTypes();

			if (
				isset($callableParms[0]) &&
				$callableParms[0] instanceof Param &&
				$callableParms[0]->var instanceof Variable
			) {
				$valueType = $this->fetchTypeByVariable($specifiedTypesInFuncCall, $callableParms[0]->var->name);
			}

			if (
				isset($callableParms[1]) &&
				$callableParms[1] instanceof Param &&
				$callableParms[1]->var instanceof Variable
			) {
				$keyType = $this->fetchTypeByVariable($specifiedTypesInFuncCall, $callableParms[1]->var->name);
			}

			if (isset($keyType) || isset($valueType)) {
				return $this->typeSpecifier->create(
					$array,
					new ArrayType($keyType ?? new MixedType(), $valueType ?? new MixedType()),
					$context,
					$scope,
				);
			}
		}

		return new SpecifiedTypes();
	}

	/**
	 * @param array<string, list{Expr, Type}> $specifiedTypes
	 */
	private function fetchTypeByVariable(array $specifiedTypes, string $variableName): ?Type
	{
		$specifiedTypeOfKey = array_find(
			$specifiedTypes,
			static fn ($specifiedType) => $specifiedType[0] instanceof Expr\Variable && $specifiedType[0]->name === $variableName,
		);

		if (isset($specifiedTypeOfKey)) {
			return $specifiedTypeOfKey[1];
		}

		return null;
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
