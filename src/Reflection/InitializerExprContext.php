<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;

/** @api */
class InitializerExprContext
{

	private function __construct(private ?string $file, private ?string $className)
	{
	}

	public static function fromScope(Scope $scope): self
	{
		return new self($scope->getFile(), $scope->isInClass() ? $scope->getClassReflection()->getName() : null);
	}

	public static function fromClassReflection(ClassReflection $classReflection): self
	{
		return new self($classReflection->getFileName(), $classReflection->getName());
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

		return new self($file === false ? null : $file, $declaringFunction->getDeclaringClass()->getName());
	}

	public static function fromStubParameter(?string $className, string $stubFile): self
	{
		return new self($stubFile, $className);
	}

	public static function fromGlobalConstant(ReflectionConstant $constant): self
	{
		return new self($constant->getFileName(), null);
	}

	public function getFile(): ?string
	{
		return $this->file;
	}

	public function getClassName(): ?string
	{
		return $this->className;
	}

}
