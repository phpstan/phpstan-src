<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;
use function strtolower;

class MethodExistsTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
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
			&& $context->truthy()
			&& count($node->getArgs()) >= 2;
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$objectType = $scope->getType($node->getArgs()[0]->value);
		if (!$objectType instanceof ObjectType) {
			if ($objectType->isString()->yes()) {
				return new SpecifiedTypes([], []);
			}
		}

		$methodNameType = $scope->getType($node->getArgs()[1]->value);
		if (!$methodNameType instanceof ConstantStringType) {
			return new SpecifiedTypes([], []);
		}

		return $this->typeSpecifier->create(
			$node->getArgs()[0]->value,
			new UnionType([
				$this->resolveObjectMethodType($methodNameType->getValue()),
				new ClassStringType(),
			]),
			$context,
			false,
			$scope,
		);
	}

	private function resolveObjectMethodType(string $methodName): Type
	{
		if (strtolower($methodName) === '__tostring') {
			$stringableType = new ObjectType('Stringable');
			$classReflection = $stringableType->getClassReflection();
			if ($classReflection !== null && $classReflection->isBuiltin()) {
				return $stringableType;
			}
		}

		return new IntersectionType([
			new ObjectWithoutClassType(),
			new HasMethodType($methodName),
		]);
	}

}
