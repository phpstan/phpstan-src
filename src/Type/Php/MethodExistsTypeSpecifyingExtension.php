<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\TypeCombinator;
use function count;

#[AutowiredService]
final class MethodExistsTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
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
		return $functionReflection->getName() === 'method_exists'
			&& $context->true()
			&& count($node->getArgs()) >= 2;
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$specifiedTypes = $this->typeSpecifier->create(
			new FuncCall(new FullyQualified('method_exists'), $node->getRawArgs()),
			new ConstantBooleanType(true),
			$context,
			$scope,
		);

		$methodNameTypes = $scope->getType($node->getArgs()[1]->value)->getConstantStrings();
		if ($methodNameTypes === []) {
			return $specifiedTypes;
		}

		$objectType = $scope->getType($node->getArgs()[0]->value);
		if ($objectType->isString()->yes()) {
			if ($objectType->isClassString()->yes()) {
				$types = [];
				foreach ($methodNameTypes as $methodNameType) {
					$types[] = TypeCombinator::intersect(
						$objectType,
						new HasMethodType($methodNameType->getValue()),
					);
				}

				return $specifiedTypes->unionWith($this->typeSpecifier->create(
					$node->getArgs()[0]->value,
					TypeCombinator::union(...$types),
					$context,
					$scope,
				));
			}

			return $specifiedTypes;
		}

		$types = [];
		foreach ($methodNameTypes as $methodNameType) {
			$types[] = TypeCombinator::union(
				TypeCombinator::intersect(
					new ObjectWithoutClassType(),
					new HasMethodType($methodNameType->getValue()),
				),
				new ClassStringType(),
			);
		}

		return $specifiedTypes->unionWith($this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			TypeCombinator::union(...$types),
			$context,
			$scope,
		));
	}

}
