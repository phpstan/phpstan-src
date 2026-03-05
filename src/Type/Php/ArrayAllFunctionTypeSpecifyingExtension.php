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
			$specifiedTypesInCallable = $this->typeSpecifier->specifyTypesInCondition($scope, $callable->expr, $context)->getSureTypes();
			$callableParm = $callable->params[0];
			if (!$callableParm instanceof Expr\Variable || !key_exists("$" . $callableParm->name, $specifiedTypesInCallable)) {
				return new SpecifiedTypes();
			}
			$ItemType = $specifiedTypesInCallable["$" . $callableParm->name][1];
			return $this->typeSpecifier->create(
				$array,
				new ArrayType(new MixedType(), $ItemType),
				$context,
				$scope
			);
		}

		return new SpecifiedTypes();
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
