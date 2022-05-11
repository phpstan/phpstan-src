<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;

class InitializerExprContext
{

	public function __construct(private ?string $file, private ?ClassReflection $classReflection = null)
	{
	}

	public static function fromScope(Scope $scope): self
	{
		return new self($scope->getFile(), $scope->getClassReflection());
	}

	public static function fromClassReflection(ClassReflection $classReflection): self
	{
		return new self($classReflection->getFileName(), $classReflection);
	}

	public static function fromReflectionParameter(ReflectionParameter $parameter): self
	{
		$declaringFunction = $parameter->getDeclaringFunction();
		if ($declaringFunction instanceof ReflectionFunction) {
			$file = $declaringFunction->getFileName();
			return new self($file === false ? null : $file, null);
		}

		// method

		$file = $declaringFunction->getFileName();
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		$className = $declaringFunction->getDeclaringClass()->getName();
		if (!$reflectionProvider->hasClass($className)) {
			return new self($file === false ? null : $file, null);
		}

		$classReflection = $reflectionProvider->getClass($className);

		return new self($file === false ? null : $file, $classReflection);
	}

	public static function fromStubParameter(?string $className, string $stubFile): self
	{
		if ($className === null) {
			return new self($stubFile, null);
		}

		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if (!$reflectionProvider->hasClass($className)) {
			return new self($stubFile, null);
		}
		$classReflection = $reflectionProvider->getClass($className);

		return new self($stubFile, $classReflection);
	}

	public function getFile(): ?string
	{
		return $this->file;
	}

	public function getClass(): ?ClassReflection
	{
		return $this->classReflection;
	}

}
