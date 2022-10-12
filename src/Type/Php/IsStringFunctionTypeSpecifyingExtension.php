<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use function strtolower;

class IsStringFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'is_string'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			throw new ShouldNotHappenException();
		}

		if (!isset($node->getArgs()[0])) {
			return new SpecifiedTypes();
		}

		$value = $node->getArgs()[0]->value;
		$valueType = $scope->getType($value);

		$resultType = new StringType();
		if ($valueType->isCallable()->yes()) {
			$resultType = TypeCombinator::intersect($resultType, new AccessoryNonEmptyStringType());
		}

		return $this->typeSpecifier->create($value, $resultType, $context, false, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
