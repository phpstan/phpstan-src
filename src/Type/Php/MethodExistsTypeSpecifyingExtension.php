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
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class MethodExistsTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

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
		$args = $node->getArgs();
		$methodNameType = $scope->getType($args[1]->value);
		if (!$methodNameType instanceof ConstantStringType) {
			return $this->typeSpecifier->create(
				new FuncCall(new FullyQualified('method_exists'), $node->getRawArgs()),
				new ConstantBooleanType(true),
				$context,
				$scope,
			);
		}

		$objectType = $scope->getType($args[0]->value);
		$funcCallSpec = $this->typeSpecifier->create(
			new FuncCall(new FullyQualified('method_exists'), $node->getRawArgs()),
			new ConstantBooleanType(true),
			$context,
			$scope,
		);
		if ($objectType->isString()->yes()) {
			if ($objectType->isClassString()->yes()) {
				foreach ($objectType->getConstantStrings() as $constantString) {
					if ($this->reflectionProvider->hasClass($constantString->getValue())) {
						$classReflection = $this->reflectionProvider->getClass($constantString->getValue());
						if ($classReflection->hasMethod($methodNameType->getValue()) && !$classReflection->hasNativeMethod($methodNameType->getValue())) {
							return $funcCallSpec;
						}
					}
				}

				return $this->typeSpecifier->create(
					$args[0]->value,
					new IntersectionType([
						$objectType,
						new HasMethodType($methodNameType->getValue()),
					]),
					$context,
					$scope,
				);
			}

			return new SpecifiedTypes([], []);
		}

		foreach ($objectType->getObjectClassReflections() as $classReflection) {
			if ($classReflection->hasMethod($methodNameType->getValue()) && !$classReflection->hasNativeMethod($methodNameType->getValue())) {
				return $funcCallSpec;
			}
		}

		return $this->typeSpecifier->create(
			$args[0]->value,
			new UnionType([
				new IntersectionType([
					new ObjectWithoutClassType(),
					new HasMethodType($methodNameType->getValue()),
				]),
				new ClassStringType(),
			]),
			$context,
			$scope,
		);
	}

}
