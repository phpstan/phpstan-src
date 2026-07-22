<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;

/**
 * Opens the output buffer (increases the tracked `ob_get_level()`) only in the
 * branch where `ob_start()` is known to have returned a truthy value. An
 * unchecked `ob_start()` may have failed, so the buffer is not assumed active.
 */
#[AutowiredService]
final class ObStartFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $functionReflection->getName() === 'ob_start' && $context->truthy();
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$types = new SpecifiedTypes();
		foreach ([new Name('ob_get_level'), new Name\FullyQualified('ob_get_level')] as $name) {
			$obGetLevelCall = new FuncCall($name, []);
			$newLevelType = $scope->getType(new BinaryOp\Plus(
				new TypeExpr($scope->getType($obGetLevelCall)),
				new TypeExpr(new ConstantIntegerType(1)),
			));

			$types = $types->unionWith($this->typeSpecifier->create(
				$obGetLevelCall,
				$newLevelType,
				$context,
				$scope,
			)->setAlwaysOverwriteTypes());
		}

		return $types;
	}

}
