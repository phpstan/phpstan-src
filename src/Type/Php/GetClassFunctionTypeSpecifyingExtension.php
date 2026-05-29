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
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\ObjectType;
use function count;
use function in_array;
use function strtolower;

/**
 * Narrows the argument of get_class()/get_debug_type() when its result is compared against a
 * class-string, e.g. `get_class($a) === Foo::class` narrows $a to Foo. Driven by the narrowed return type
 * carried by the comparison (TypeSpecifierContext::getNarrowedReturnType()).
 */
#[AutowiredService]
final class GetClassFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $context->getNarrowedReturnType() !== null
			&& $context->true()
			&& $node->name instanceof Name
			&& !$node->isFirstClassCallable()
			&& isset($node->getArgs()[0])
			&& in_array(strtolower($functionReflection->getName()), ['get_class', 'get_debug_type'], true);
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$narrowedReturnType = $context->getNarrowedReturnType();
		if ($narrowedReturnType === null) {
			return new SpecifiedTypes();
		}

		$argExpr = $node->getArgs()[0]->value;

		$constantStrings = $narrowedReturnType->getConstantStrings();
		if (count($constantStrings) === 1 && $this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
			$argType = new ObjectType(
				$constantStrings[0]->getValue(),
				classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal(),
			);
		} elseif ($narrowedReturnType->getClassStringObjectType()->isObject()->yes()) {
			$argType = $narrowedReturnType->getClassStringObjectType();
		} else {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create($argExpr, $argType, $context, $scope)
			->unionWith($this->typeSpecifier->create($node, $narrowedReturnType, $context, $scope));
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
