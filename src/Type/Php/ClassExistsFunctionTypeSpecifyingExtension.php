<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use function ltrim;

#[AutowiredService]
final class ClassExistsFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return in_array($functionReflection->getName(), [
			'class_exists',
			'interface_exists',
			'trait_exists',
			'enum_exists',
		], true) && isset($node->getArgs()[0]) && $context->true();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		$argType = $scope->getType($args[0]->value);
		$functionName = $functionReflection->getName();

		if ($this->isCallAlwaysFalse($functionName, $argType)) {
			return $this->typeSpecifier->create(
				$args[0]->value,
				new NeverType(),
				$context,
				$scope,
			);
		}

		// class_exists() will only assure one of the classes to exist.
		$constantStrings = $argType->getConstantStrings();
		if (count($constantStrings) === 1) {
			if ($functionReflection->getName() === '') {
				throw new ShouldNotHappenException();
			}
			return $this->typeSpecifier->create(
				new AlwaysRememberedExpr(
					new FuncCall(new FullyQualified($functionReflection->getName()), [
						new Arg(new String_(ltrim($constantStrings[0]->getValue(), '\\'))),
					]),
					new BooleanType(),
					new BooleanType(),
				),
				new ConstantBooleanType(true),
				$context,
				$scope,
			)->unionWith(
				$this->typeSpecifier->create(
					new AlwaysRememberedExpr(
						new FuncCall(new FullyQualified('class_exists'), [
							new Arg(new String_(ltrim($constantStrings[0]->getValue(), '\\'))),
						]),
						new BooleanType(),
						new BooleanType(),
					),
					new ConstantBooleanType(true),
					$context,
					$scope,
				),
			);
		}

		$narrowedType = new ClassStringType();
		if ($functionReflection->getName() === 'enum_exists') {
			$narrowedType = new GenericClassStringType(new ObjectType('UnitEnum'));
		}

		return $this->typeSpecifier->create(
			$args[0]->value,
			$narrowedType,
			$context,
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	private function isCallAlwaysFalse(string $functionName, Type $argType): bool
	{
		$constantStrings = $argType->getConstantStrings();
		if ($constantStrings === []) {
			return false;
		}

		foreach ($constantStrings as $constantString) {
			$name = ltrim($constantString->getValue(), '\\');
			if (!$this->reflectionProvider->hasClass($name)) {
				return false;
			}
			if ($this->matchesFunctionKind($functionName, $this->reflectionProvider->getClass($name))) {
				return false;
			}
		}

		return true;
	}

	private function matchesFunctionKind(string $functionName, ClassReflection $reflection): bool
	{
		switch ($functionName) {
			case 'class_exists':
				return !$reflection->isInterface() && !$reflection->isTrait();
			case 'interface_exists':
				return $reflection->isInterface();
			case 'trait_exists':
				return $reflection->isTrait();
			case 'enum_exists':
				return $reflection->isEnum();
			default:
				return true;
		}
	}

}
