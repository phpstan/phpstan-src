<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use Nette\Utils\Strings;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionWithFilename;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;

class ClassBlacklistReflectionProvider implements ReflectionProvider
{

	private ReflectionProvider $reflectionProvider;

	private SourceStubber $phpStormStubsSourceStubber;

	/** @var string[] */
	private array $patterns;

	private ?string $singleReflectionFile;

	/**
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param string[] $patterns
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		SourceStubber $phpStormStubsSourceStubber,
		array $patterns,
		?string $singleReflectionFile
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->patterns = $patterns;
		$this->singleReflectionFile = $singleReflectionFile;
	}

	public function hasClass(string $className): bool
	{
		if ($this->isClassBlacklisted($className)) {
			return false;
		}

		$has = $this->reflectionProvider->hasClass($className);
		if (!$has) {
			return false;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if ($this->singleReflectionFile !== null) {
			if ($classReflection->getFileName() === $this->singleReflectionFile) {
				return false;
			}
		}

		foreach ($classReflection->getParentClassesNames() as $parentClassName) {
			if ($this->isClassBlacklisted($parentClassName)) {
				return false;
			}
		}

		foreach ($classReflection->getNativeReflection()->getInterfaceNames() as $interfaceName) {
			if ($this->isClassBlacklisted($interfaceName)) {
				return false;
			}
		}

		return true;
	}

	private function isClassBlacklisted(string $className): bool
	{
		if ($this->phpStormStubsSourceStubber->hasClass($className) && $className !== \Generator::class) {
			// check that userland class isn't aliased to the same name as a class from stubs
			if (!class_exists($className, false)) {
				return true;
			}
			$reflection = new \ReflectionClass($className);
			if ($reflection->getFileName() === false) {
				return true;
			}
		}

		foreach ($this->patterns as $pattern) {
			if (Strings::match($className, $pattern) !== null) {
				return true;
			}
		}

		return false;
	}

	public function getClass(string $className): ClassReflection
	{
		if (!$this->hasClass($className)) {
			throw new \PHPStan\Broker\ClassNotFoundException($className);
		}

		return $this->reflectionProvider->getClass($className);
	}

	public function getClassName(string $className): string
	{
		if (!$this->hasClass($className)) {
			throw new \PHPStan\Broker\ClassNotFoundException($className);
		}

		return $this->reflectionProvider->getClassName($className);
	}

	public function getAnonymousClassReflection(\PhpParser\Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		return $this->reflectionProvider->getAnonymousClassReflection($classNode, $scope);
	}

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		$has = $this->reflectionProvider->hasFunction($nameNode, $scope);
		if (!$has) {
			return false;
		}

		if ($this->singleReflectionFile === null) {
			return true;
		}

		$functionReflection = $this->reflectionProvider->getFunction($nameNode, $scope);
		if (!$functionReflection instanceof ReflectionWithFilename) {
			return true;
		}

		return $functionReflection->getFileName() !== $this->singleReflectionFile;
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

}
