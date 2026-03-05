<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
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
		$callable = $args[1]->value;


		if ($callable instanceof Expr\ArrowFunction && $callable->expr instanceof Expr\FuncCall) {

			$callableParms = $callable->params;
			$specifiedTypesInFuncCall = $this->typeSpecifier->specifyTypesInCondition($scope, $callable->expr, $context)->getSureTypes();

			if(count($callableParms) >= 1 && $callableParms[0] instanceof Expr\Variable) {

				$callableParmValueName = $callableParms[0]->name;
				$specifiedTypeOfValue = array_find(
					$specifiedTypesInFuncCall,
					fn($specifiedType) => $specifiedType[0] instanceof Expr\Variable && $specifiedType[0]->name === $callableParmValueName
				);

				if(isset($specifiedTypeOfValue)) {
					$valueType = $specifiedTypeOfValue[1];
				}

			}

			if(count($callableParms) >= 2 && $callableParms[1] instanceof Expr\Variable) {

				$callableParmKeyName = $callableParms[1]->name;
				$specifiedTypeOfKey = array_find(
					$specifiedTypesInFuncCall,
					fn($specifiedType) => $specifiedType[0] instanceof Expr\Variable && $specifiedType[0]->name === $callableParmKeyName
				);

				if(isset($specifiedTypeOfKey)) {
					$keyType = $specifiedTypeOfKey[1];
				}

			}

			if(isset($keyType) || isset($valueType)) {
				return $this->typeSpecifier->create(
					$array,
					new ArrayType($keyType ?? new MixedType(), $valueType ?? new MixedType()),
					$context,
					$scope
				);
			}
		}

		return new SpecifiedTypes();
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
