<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use ReflectionMethod;
use function count;
use function strpos;
use function substr;

#[AutowiredService]
final class ReflectionMethodConstructorReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getClass(): string
	{
		return ReflectionMethod::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		$firstArgType = $scope->getType($args[0]->value);

		// new ReflectionMethod($objectOrClassString, $method)
		if (count($args) >= 2) {
			$className = $this->resolveClassName($firstArgType);
			if ($className === null) {
				return null;
			}

			$methodNameType = $scope->getType($args[1]->value);
			$methodNames = $methodNameType->getConstantStrings();
			if (count($methodNames) !== 1) {
				return null;
			}

			return $this->build($className, $methodNames[0]->getValue());
		}

		// new ReflectionMethod('Class::method')
		$classAndMethod = $firstArgType->getConstantStrings();
		if (count($classAndMethod) !== 1) {
			return null;
		}

		$value = $classAndMethod[0]->getValue();
		$separatorPos = strpos($value, '::');
		if ($separatorPos === false) {
			return null;
		}

		return $this->build(substr($value, 0, $separatorPos), substr($value, $separatorPos + 2));
	}

	private function resolveClassName(Type $type): ?string
	{
		// new ReflectionMethod($classString, ...) — covers both Foo::class and class-string<Foo>
		$classStringObjectNames = $type->getClassStringObjectType()->getObjectClassNames();
		if (count($classStringObjectNames) === 1) {
			return $classStringObjectNames[0];
		}

		// new ReflectionMethod($object, ...)
		$objectClassNames = $type->getObjectClassNames();
		return count($objectClassNames) === 1 ? $objectClassNames[0] : null;
	}

	private function build(string $className, string $methodName): ?Type
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return null;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$classReflection->hasMethod($methodName)) {
			return null;
		}

		return new GenericObjectType(ReflectionMethod::class, [
			new ObjectType($className),
			new ConstantStringType($methodName),
		]);
	}

}
