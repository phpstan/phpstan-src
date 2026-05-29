<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

/**
 * Narrows the argument of get_parent_class() when its result is compared against a class-string,
 * e.g. `get_parent_class($a) === Foo::class` narrows $a to Foo or class-string<Foo>. Driven by the
 * narrowed return type carried by the comparison (TypeSpecifierContext::getNarrowedReturnType()).
 */
#[AutowiredService]
final class GetParentClassFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $context->getNarrowedReturnType() !== null
			&& $context->true()
			&& $node->name instanceof Name
			&& !$node->isFirstClassCallable()
			&& isset($node->getArgs()[0])
			&& strtolower($functionReflection->getName()) === 'get_parent_class';
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$narrowedReturnType = $context->getNarrowedReturnType();
		if ($narrowedReturnType === null) {
			return new SpecifiedTypes();
		}

		$constantStrings = $narrowedReturnType->getConstantStrings();
		if (count($constantStrings) !== 1) {
			return new SpecifiedTypes();
		}

		$argValue = $node->getArgs()[0]->value;
		$argType = $scope->getType($argValue);
		$objectType = new ObjectType($constantStrings[0]->getValue());
		$classStringType = new GenericClassStringType($objectType);

		if ($argType->isString()->yes()) {
			return $this->typeSpecifier->create($argValue, $classStringType, $context, $scope);
		}

		if ($argType->isObject()->yes()) {
			return $this->typeSpecifier->create($argValue, $objectType, $context, $scope);
		}

		return $this->typeSpecifier->create($argValue, TypeCombinator::union($objectType, $classStringType), $context, $scope);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
