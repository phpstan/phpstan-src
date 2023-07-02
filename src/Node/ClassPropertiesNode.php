<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Node\Method\MethodCall;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_keys;
use function count;
use function in_array;

/** @api */
class ClassPropertiesNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param ClassPropertyNode[] $properties
	 * @param array<int, PropertyRead|PropertyWrite> $propertyUsages
	 * @param array<int, MethodCall> $methodCalls
	 */
	public function __construct(
		private ClassLike $class,
		private ReadWritePropertiesExtensionProvider $readWritePropertiesExtensionProvider,
		private array $properties,
		private array $propertyUsages,
		private array $methodCalls,
	)
	{
		parent::__construct($class->getAttributes());
	}

	public function getClass(): ClassLike
	{
		return $this->class;
	}

	/**
	 * @return ClassPropertyNode[]
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return array<int, PropertyRead|PropertyWrite>
	 */
	public function getPropertyUsages(): array
	{
		return $this->propertyUsages;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_ClassPropertiesNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

	/**
	 * @param string[] $constructors
	 * @param ReadWritePropertiesExtension[]|null $extensions
	 * @return array{array<string, ClassPropertyNode>, array<array{string, int, ClassPropertyNode}>, array<array{string, int, ClassPropertyNode}>}
	 */
	public function getUninitializedProperties(
		Scope $scope,
		array $constructors,
		?array $extensions = null,
	): array
	{
		if (!$this->getClass() instanceof Class_) {
			return [[], [], []];
		}
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();

		$properties = [];
		$originalProperties = [];
		foreach ($this->getProperties() as $property) {
			if ($property->isStatic()) {
				continue;
			}
			if ($property->getNativeType() === null) {
				continue;
			}
			if ($property->getDefault() !== null) {
				continue;
			}
			$originalProperties[$property->getName()] = $property;
			if ($property->isPromoted()) {
				continue;
			}
			$properties[$property->getName()] = $property;
		}

		if ($extensions === null) {
			$extensions = $this->readWritePropertiesExtensionProvider->getExtensions();
		}

		foreach (array_keys($properties) as $name) {
			foreach ($extensions as $extension) {
				if (!$classReflection->hasNativeProperty($name)) {
					continue;
				}
				$propertyReflection = $classReflection->getNativeProperty($name);
				if (!$extension->isInitialized($propertyReflection, $name)) {
					continue;
				}
				unset($properties[$name]);
				break;
			}
		}

		if ($constructors === []) {
			return [$properties, [], []];
		}
		$methodsCalledFromConstructor = $this->getMethodsCalledFromConstructor($classReflection, $this->methodCalls, $constructors);
		$prematureAccess = [];
		$additionalAssigns = [];

		foreach ($this->getPropertyUsages() as $usage) {
			$fetch = $usage->getFetch();
			if (!$fetch instanceof PropertyFetch) {
				continue;
			}
			$usageScope = $usage->getScope();
			if ($usageScope->getFunction() === null) {
				continue;
			}
			$function = $usageScope->getFunction();
			if (!$function instanceof MethodReflection) {
				continue;
			}
			if ($function->getDeclaringClass()->getName() !== $classReflection->getName()) {
				continue;
			}
			if (!in_array($function->getName(), $methodsCalledFromConstructor, true)) {
				continue;
			}

			if (!$fetch->name instanceof Identifier) {
				continue;
			}
			$propertyName = $fetch->name->toString();
			$fetchedOnType = $usageScope->getType($fetch->var);

			$propertyReflection = $usageScope->getPropertyReflection($fetchedOnType, $propertyName);
			if ($propertyReflection === null) {
				continue;
			}
			if ($propertyReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
				continue;
			}

			if ($usage instanceof PropertyWrite) {
				if (array_key_exists($propertyName, $properties)) {
					unset($properties[$propertyName]);
				}

				if (array_key_exists($propertyName, $originalProperties)) {
					$hasInitialization = $usageScope->hasExpressionType(new PropertyInitializationExpr($propertyName));
					if (!$hasInitialization->no()) {
						$additionalAssigns[] = [
							$propertyName,
							$fetch->getLine(),
							$originalProperties[$propertyName],
						];
					}
				}
			} elseif (array_key_exists($propertyName, $originalProperties)) {
				$hasInitialization = $usageScope->hasExpressionType(new PropertyInitializationExpr($propertyName));
				if (!$hasInitialization->yes()) {
					$prematureAccess[] = [
						$propertyName,
						$fetch->getLine(),
						$originalProperties[$propertyName],
					];
				}
			}
		}

		return [
			$properties,
			$prematureAccess,
			$additionalAssigns,
		];
	}

	/**
	 * @param MethodCall[] $methodCalls
	 * @param string[] $methods
	 * @return string[]
	 */
	private function getMethodsCalledFromConstructor(
		ClassReflection $classReflection,
		array $methodCalls,
		array $methods,
	): array
	{
		$originalCount = count($methods);
		foreach ($methodCalls as $methodCall) {
			$methodCallNode = $methodCall->getNode();
			if ($methodCallNode instanceof Array_) {
				continue;
			}
			if (!$methodCallNode->name instanceof Identifier) {
				continue;
			}
			$callScope = $methodCall->getScope();
			if ($methodCallNode instanceof Node\Expr\MethodCall) {
				$calledOnType = $callScope->getType($methodCallNode->var);
			} else {
				if (!$methodCallNode->class instanceof Name) {
					continue;
				}

				$calledOnType = $callScope->resolveTypeByName($methodCallNode->class);
			}

			$methodName = $methodCallNode->name->toString();
			if (in_array($methodName, $methods, true)) {
				continue;
			}
			$methodReflection = $callScope->getMethodReflection($calledOnType, $methodName);
			if ($methodReflection === null) {
				continue;
			}
			if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
				continue;
			}
			$inMethod = $callScope->getFunction();
			if (!$inMethod instanceof MethodReflection) {
				continue;
			}
			if (!in_array($inMethod->getName(), $methods, true)) {
				continue;
			}
			$methods[] = $methodName;
		}

		if ($originalCount === count($methods)) {
			return $methods;
		}

		return $this->getMethodsCalledFromConstructor($classReflection, $methodCalls, $methods);
	}

}
