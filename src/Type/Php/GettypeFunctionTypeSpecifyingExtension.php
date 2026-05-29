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
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use function strtolower;

/**
 * Narrows the argument of gettype() when its result is compared against a known type name,
 * e.g. `gettype($a) === 'string'` narrows $a to string. Driven by the narrowed return type carried by the
 * comparison (TypeSpecifierContext::getNarrowedReturnType()).
 */
#[AutowiredService]
final class GettypeFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $context->getNarrowedReturnType() !== null
			&& $node->name instanceof Name
			&& !$node->isFirstClassCallable()
			&& isset($node->getArgs()[0])
			&& strtolower($functionReflection->getName()) === 'gettype';
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

		$argType = $this->mapGettypeValueToType($constantStrings[0]->getValue());
		if ($argType === null) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create($node, $narrowedReturnType, $context, $scope)
			->unionWith($this->typeSpecifier->create($node->getArgs()[0]->value, $argType, $context, $scope));
	}

	private function mapGettypeValueToType(string $value): ?Type
	{
		if ($value === 'string') {
			return new StringType();
		}
		if ($value === 'array') {
			return new ArrayType(new MixedType(), new MixedType());
		}
		if ($value === 'boolean') {
			return new BooleanType();
		}
		if (in_array($value, ['resource', 'resource (closed)'], true)) {
			return new ResourceType();
		}
		if ($value === 'integer') {
			return new IntegerType();
		}
		if ($value === 'double') {
			return new FloatType();
		}
		if ($value === 'NULL') {
			return new NullType();
		}
		if ($value === 'object') {
			return new ObjectWithoutClassType();
		}

		return null;
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
