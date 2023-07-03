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
use PHPStan\TrinaryLogic;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_keys;
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
		$initialInitializedProperties = [];
		$initializedProperties = [];
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
			$is = TrinaryLogic::createFromBoolean($property->isPromoted());
			$initialInitializedProperties[$property->getName()] = $is;
			foreach ($constructors as $constructor) {
				$initializedProperties[$constructor][$property->getName()] = $is;
			}
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

		$methodsCalledFromConstructor = $this->getMethodsCalledFromConstructor($classReflection, $this->methodCalls, $initialInitializedProperties, $initializedProperties, $constructors);
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
			if (!array_key_exists($function->getName(), $methodsCalledFromConstructor)) {
				continue;
			}

			$initializedPropertiesMap = $methodsCalledFromConstructor[$function->getName()];

			if (!$fetch->name instanceof Identifier) {
				continue;
			}
			$propertyName = $fetch->name->toString();
			$fetchedOnType = $usageScope->getType($fetch->var);
			if (TypeUtils::findThisType($fetchedOnType) === null) {
				continue;
			}

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

				if (array_key_exists($propertyName, $initializedPropertiesMap)) {
					$hasInitialization = $initializedPropertiesMap[$propertyName]->or($usageScope->hasExpressionType(new PropertyInitializationExpr($propertyName)));
					if (!$hasInitialization->no() && !$usage->isPromotedPropertyWrite()) {
						$additionalAssigns[] = [
							$propertyName,
							$fetch->getLine(),
							$originalProperties[$propertyName],
						];
					}
				}
			} elseif (array_key_exists($propertyName, $initializedPropertiesMap)) {
				$hasInitialization = $initializedPropertiesMap[$propertyName]->or($usageScope->hasExpressionType(new PropertyInitializationExpr($propertyName)));
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
	 * @param array<string, TrinaryLogic> $initialInitializedProperties
	 * @param array<string, array<string, TrinaryLogic>> $initializedProperties
	 * @return array<string, array<string, TrinaryLogic>>
	 */
	private function getMethodsCalledFromConstructor(
		ClassReflection $classReflection,
		array $methodCalls,
		array $initialInitializedProperties,
		array $initializedProperties,
		array $methods,
	): array
	{
		$originalMap = $initializedProperties;
		$originalMethods = $methods;
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

			$inMethod = $callScope->getFunction();
			if (!$inMethod instanceof MethodReflection) {
				continue;
			}
			if (!in_array($inMethod->getName(), $methods, true)) {
				continue;
			}

			$methodName = $methodCallNode->name->toString();
			if (array_key_exists($methodName, $initializedProperties)) {
				foreach ($this->getInitializedProperties($callScope, $initializedProperties[$inMethod->getName()] ?? $initialInitializedProperties) as $propertyName => $isInitialized) {
					$initializedProperties[$methodName][$propertyName] = $initializedProperties[$methodName][$propertyName]->and($isInitialized);
				}
				continue;
			}
			$methodReflection = $callScope->getMethodReflection($calledOnType, $methodName);
			if ($methodReflection === null) {
				continue;
			}
			if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
				continue;
			}
			$initializedProperties[$methodName] = $this->getInitializedProperties($callScope, $initializedProperties[$inMethod->getName()] ?? $initialInitializedProperties);
			$methods[] = $methodName;
		}

		if ($originalMap === $initializedProperties && $originalMethods === $methods) {
			return $initializedProperties;
		}

		return $this->getMethodsCalledFromConstructor($classReflection, $methodCalls, $initialInitializedProperties, $initializedProperties, $methods);
	}

	/**
	 * @param array<string, TrinaryLogic> $initialInitializedProperties
	 * @return array<string, TrinaryLogic>
	 */
	private function getInitializedProperties(Scope $scope, array $initialInitializedProperties): array
	{
		foreach ($initialInitializedProperties as $propertyName => $isInitialized) {
			$initialInitializedProperties[$propertyName] = $isInitialized->or($scope->hasExpressionType(new PropertyInitializationExpr($propertyName)));
		}

		return $initialInitializedProperties;
	}

}
