<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use Nette\Utils\Strings;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;

class ClassBlacklistReflectionProvider implements ReflectionProvider
{

	private ReflectionProvider $reflectionProvider;

	private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;

	/** @var string[] */
	private array $patterns;

	private ?string $singleReflectionInsteadOfFile;

	/**
	 * @param string[] $patterns
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		array $patterns,
		?string $singleReflectionInsteadOfFile
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->patterns = $patterns;
		$this->singleReflectionInsteadOfFile = $singleReflectionInsteadOfFile;
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
		if ($this->singleReflectionInsteadOfFile !== null) {
			if ($classReflection->getFileName() === $this->singleReflectionInsteadOfFile) {
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
		if ($this->phpStormStubsSourceStubber->hasClass($className)) {
			// check that userland class isn't aliased to the same name as a class from stubs
			if (!class_exists($className, false)) {
				return true;
			}
			if (in_array(strtolower($className), [
				'reflectionuniontype',
				'attribute',
				'returntypewillchange',
				'reflectionintersectiontype',
				'unitenum',
				'backedenum',
				'reflectionenum',
				'reflectionenumunitcase',
				'reflectionenumbackedcase',
			], true)) {
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

	public function supportsAnonymousClasses(): bool
	{
		return false;
	}

	public function getAnonymousClassReflection(\PhpParser\Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		throw new \PHPStan\ShouldNotHappenException();
	}

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		$has = $this->reflectionProvider->hasFunction($nameNode, $scope);
		if (!$has) {
			return false;
		}

		if ($this->singleReflectionInsteadOfFile === null) {
			return true;
		}

		$functionReflection = $this->reflectionProvider->getFunction($nameNode, $scope);

		return $functionReflection->getFileName() !== $this->singleReflectionInsteadOfFile;
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
