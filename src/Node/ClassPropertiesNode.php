<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Method\MethodCall;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;

class ClassPropertiesNode extends NodeAbstract implements VirtualNode
{

	private ClassLike $class;

	/** @var Property[] */
	private array $properties;

	/** @var array<int, PropertyRead|PropertyWrite> */
	private array $propertyUsages;

	/** @var array<int, MethodCall> */
	private array $methodCalls;

	/**
	 * @param ClassLike $class
	 * @param Property[] $properties
	 * @param array<int, PropertyRead|PropertyWrite> $propertyUsages
	 * @param array<int, MethodCall> $methodCalls
	 */
	public function __construct(ClassLike $class, array $properties, array $propertyUsages, array $methodCalls)
	{
		parent::__construct($class->getAttributes());
		$this->class = $class;
		$this->properties = $properties;
		$this->propertyUsages = $propertyUsages;
		$this->methodCalls = $methodCalls;
	}

	public function getClass(): ClassLike
	{
		return $this->class;
	}

	/**
	 * @return Property[]
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
	 * @return array{array<string, Property>, array<array{string, int}>}
	 */
	public function getUninitializedProperties(Scope $scope): array
	{
		if (!$this->getClass() instanceof Class_) {
			return [[], []];
		}
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$constructor = null;
		$classReflection = $scope->getClassReflection();
		if ($classReflection->hasConstructor()) {
			$constructor = $classReflection->getConstructor();
		}

		$properties = [];
		foreach ($this->getProperties() as $property) {
			if ($property->isStatic()) {
				continue;
			}
			if ($property->type === null) {
				continue;
			}
			foreach ($property->props as $prop) {
				if ($prop->default !== null) {
					continue;
				}
				$properties[$prop->name->toString()] = $property;
			}
		}

		if ($constructor === null) {
			return [$properties, []];
		}
		$classType = new ObjectType($scope->getClassReflection()->getName());
		$methodsCalledFromConstructor = $this->getMethodsCalledFromConstructor($classType, $this->methodCalls, [$constructor->getName()]);
		$prematureAccess = [];
		foreach ($this->getPropertyUsages() as $usage) {
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

			$fetch = $usage->getFetch();
			if (!$fetch->name instanceof Identifier) {
				continue;
			}
			$propertyName = $fetch->name->toString();
			if (!array_key_exists($propertyName, $properties)) {
				continue;
			}
			$fetchedOnType = $usageScope->getType($fetch->var);
			if ($classType->isSuperTypeOf($fetchedOnType)->no()) {
				continue;
			}
			if ($fetchedOnType instanceof MixedType) {
				continue;
			}

			if ($usage instanceof PropertyWrite) {
				unset($properties[$propertyName]);
			} elseif (array_key_exists($propertyName, $properties)) {
				$prematureAccess[] = [
					$propertyName,
					$fetch->getLine(),
				];
			}
		}

		return [
			$properties,
			$prematureAccess,
		];
	}

	/**
	 * @param ObjectType $classType
	 * @param MethodCall[] $methodCalls
	 * @param string[] $methods
	 * @return string[]
	 */
	private function getMethodsCalledFromConstructor(
		ObjectType $classType,
		array $methodCalls,
		array $methods
	): array
	{
		$originalCount = count($methods);
		foreach ($methodCalls as $methodCall) {
			$methodCallNode = $methodCall->getNode();
			if (!$methodCallNode->name instanceof Identifier) {
				continue;
			}
			$callScope = $methodCall->getScope();
			if ($methodCallNode instanceof \PhpParser\Node\Expr\MethodCall) {
				$calledOnType = $callScope->getType($methodCallNode->var);
			} else {
				if (!$methodCallNode->class instanceof Name) {
					continue;
				}
				$calledOnType = new ObjectType($callScope->resolveName($methodCallNode->class));
			}
			if ($classType->isSuperTypeOf($calledOnType)->no()) {
				continue;
			}
			if ($calledOnType instanceof MixedType) {
				continue;
			}
			$methodName = $methodCallNode->name->toString();
			if (in_array($methodName, $methods, true)) {
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

		return $this->getMethodsCalledFromConstructor($classType, $methodCalls, $methods);
	}

}
