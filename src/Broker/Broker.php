<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;

/** @api */
class Broker implements ReflectionProvider
{

	private ReflectionProvider $reflectionProvider;

	private DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider;

	private \PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider;

	/** @var string[] */
	private array $universalObjectCratesClasses;

	private static ?Broker $instance = null;

	/**
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param \PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider
	 * @param \PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider,
		OperatorTypeSpecifyingExtensionRegistryProvider $operatorTypeSpecifyingExtensionRegistryProvider,
		array $universalObjectCratesClasses
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->dynamicReturnTypeExtensionRegistryProvider = $dynamicReturnTypeExtensionRegistryProvider;
		$this->operatorTypeSpecifyingExtensionRegistryProvider = $operatorTypeSpecifyingExtensionRegistryProvider;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
	}

	public static function registerInstance(Broker $broker): void
	{
		self::$instance = $broker;
	}

	public static function getInstance(): Broker
	{
		if (self::$instance === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		return self::$instance;
	}

	public function hasClass(string $className): bool
	{
		return $this->reflectionProvider->hasClass($className);
	}

	public function getClass(string $className): ClassReflection
	{
		return $this->reflectionProvider->getClass($className);
	}

	public function getClassName(string $className): string
	{
		return $this->reflectionProvider->getClassName($className);
	}

	public function supportsAnonymousClasses(): bool
	{
		return $this->reflectionProvider->supportsAnonymousClasses();
	}

	public function getAnonymousClassReflection(\PhpParser\Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		return $this->reflectionProvider->getAnonymousClassReflection($classNode, $scope);
	}

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->reflectionProvider->hasFunction($nameNode, $scope);
	}

	public function getFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		return $this->reflectionProvider->getFunction($nameNode, $scope);
	}

	public function resolveFunctionName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->reflectionProvider->resolveFunctionName($nameNode, $scope);
	}

	public function hasConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->reflectionProvider->hasConstant($nameNode, $scope);
	}

	public function getConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		return $this->reflectionProvider->getConstant($nameNode, $scope);
	}

	public function resolveConstantName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->reflectionProvider->resolveConstantName($nameNode, $scope);
	}

	/**
	 * @return string[]
	 */
	public function getUniversalObjectCratesClasses(): array
	{
		return $this->universalObjectCratesClasses;
	}

	/**
	 * @param string $className
	 * @return \PHPStan\Type\DynamicMethodReturnTypeExtension[]
	 */
	public function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
	{
		return $this->dynamicReturnTypeExtensionRegistryProvider->getRegistry()->getDynamicMethodReturnTypeExtensionsForClass($className);
	}

	/**
	 * @param string $className
	 * @return \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[]
	 */
	public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className): array
	{
		return $this->dynamicReturnTypeExtensionRegistryProvider->getRegistry()->getDynamicStaticMethodReturnTypeExtensionsForClass($className);
	}

	/**
	 * @return OperatorTypeSpecifyingExtension[]
	 */
	public function getOperatorTypeSpecifyingExtensions(string $operator, Type $leftType, Type $rightType): array
	{
		return $this->operatorTypeSpecifyingExtensionRegistryProvider->getRegistry()->getOperatorTypeSpecifyingExtensions($operator, $leftType, $rightType);
	}

	/**
	 * @return \PHPStan\Type\DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(): array
	{
		return $this->dynamicReturnTypeExtensionRegistryProvider->getRegistry()->getDynamicFunctionReturnTypeExtensions();
	}

	/**
	 * @internal
	 * @return DynamicReturnTypeExtensionRegistryProvider
	 */
	public function getDynamicReturnTypeExtensionRegistryProvider(): DynamicReturnTypeExtensionRegistryProvider
	{
		return $this->dynamicReturnTypeExtensionRegistryProvider;
	}

	/**
	 * @internal
	 * @return \PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider
	 */
	public function getOperatorTypeSpecifyingExtensionRegistryProvider(): OperatorTypeSpecifyingExtensionRegistryProvider
	{
		return $this->operatorTypeSpecifyingExtensionRegistryProvider;
	}

}
