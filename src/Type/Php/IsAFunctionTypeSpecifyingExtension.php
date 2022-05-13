<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\MixedType;
use function count;
use function strtolower;

class IsAFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(
		private IsAFunctionTypeSpecifyingHelper $isAFunctionTypeSpecifyingHelper,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'is_a'
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (count($node->getArgs()) < 2) {
			return new SpecifiedTypes();
		}
		$objectOrClassType = $scope->getType($node->getArgs()[0]->value);
		$classType = $scope->getType($node->getArgs()[1]->value);
		$allowStringType = isset($node->getArgs()[2]) ? $scope->getType($node->getArgs()[2]->value) : new ConstantBooleanType(false);
		$allowString = !$allowStringType->equals(new ConstantBooleanType(false));

		// early exit in some cases, so we don't create false positives via IsAFunctionTypeSpecifyingHelper
		if ($classType instanceof ClassStringType && !$classType instanceof GenericClassStringType) {
			if ($objectOrClassType->isString()->yes() && $allowString) {
				return new SpecifiedTypes([], []);
			}
			if (!$objectOrClassType->isString()->yes() && !$objectOrClassType instanceof MixedType) {
				return new SpecifiedTypes([], []);
			}
		}

		if (!$classType instanceof ConstantStringType && !$context->truthy()) {
			return new SpecifiedTypes([], []);
		}

		return $this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			$this->isAFunctionTypeSpecifyingHelper->determineType($objectOrClassType, $classType, $allowString, true),
			$context,
			false,
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
