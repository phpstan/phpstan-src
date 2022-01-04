<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Broker\ConstantNotFoundException;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;

class ChainReflectionProvider implements ReflectionProvider
{

	/**
	 * @param ReflectionProvider[] $providers
	 */
	public function __construct(
		private array $providers,
	)
	{
	}

	public function hasClass(string $className): bool
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasClass($className)) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function getClass(string $className): ClassReflection
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasClass($className)) {
				continue;
			}

			return $provider->getClass($className);
		}

		throw new ClassNotFoundException($className);
	}

	public function getClassName(string $className): string
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasClass($className)) {
				continue;
			}

			return $provider->getClassName($className);
		}

		throw new ClassNotFoundException($className);
	}

	public function supportsAnonymousClasses(): bool
	{
		foreach ($this->providers as $provider) {
			if (!$provider->supportsAnonymousClasses()) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		foreach ($this->providers as $provider) {
			if (!$provider->supportsAnonymousClasses()) {
				continue;
			}

			return $provider->getAnonymousClassReflection($classNode, $scope);
		}

		throw new ShouldNotHappenException();
	}

	public function hasFunction(Node\Name $nameNode, ?Scope $scope): bool
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasFunction($nameNode, $scope)) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function getFunction(Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasFunction($nameNode, $scope)) {
				continue;
			}

			return $provider->getFunction($nameNode, $scope);
		}

		throw new FunctionNotFoundException((string) $nameNode);
	}

	public function resolveFunctionName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		foreach ($this->providers as $provider) {
			$resolvedName = $provider->resolveFunctionName($nameNode, $scope);
			if ($resolvedName === null) {
				continue;
			}

			return $resolvedName;
		}

		return null;
	}

	public function hasConstant(Node\Name $nameNode, ?Scope $scope): bool
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasConstant($nameNode, $scope)) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function getConstant(Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		foreach ($this->providers as $provider) {
			if (!$provider->hasConstant($nameNode, $scope)) {
				continue;
			}

			return $provider->getConstant($nameNode, $scope);
		}

		throw new ConstantNotFoundException((string) $nameNode);
	}

	public function resolveConstantName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		foreach ($this->providers as $provider) {
			$resolvedName = $provider->resolveConstantName($nameNode, $scope);
			if ($resolvedName === null) {
				continue;
			}

			return $resolvedName;
		}

		return null;
	}

}
