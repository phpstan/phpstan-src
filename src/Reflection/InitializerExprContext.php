<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;

class InitializerExprContext
{

	public function __construct(private ?string $file)
	{
	}

	public static function fromScope(Scope $scope): self
	{
		return new self($scope->getFile());
	}

	public static function fromClassReflection(ClassReflection $classReflection): self
	{
		return new self($classReflection->getFileName());
	}

	public static function fromReflectionParameter(ReflectionParameter $parameter): self
	{
		$declaringFunction = $parameter->getDeclaringFunction();
		if ($declaringFunction instanceof ReflectionFunction) {
			$file = $declaringFunction->getFileName();
			return new self($file === false ? null : $file);
		}

		// method

		$file = $declaringFunction->getFileName();
		return new self($file === false ? null : $file);
	}

	public function getFile(): ?string
	{
		return $this->file;
	}

}
