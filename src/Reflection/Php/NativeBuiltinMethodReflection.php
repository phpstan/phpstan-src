<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\TrinaryLogic;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use function method_exists;

class NativeBuiltinMethodReflection implements BuiltinMethodReflection
{

	private ReflectionMethod $reflection;

	public function __construct(ReflectionMethod $reflection)
	{
		$this->reflection = $reflection;
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getReflection(): ?ReflectionMethod
	{
		return $this->reflection;
	}

	public function getFileName(): ?string
	{
		$fileName = $this->reflection->getFileName();
		if ($fileName === false) {
			return null;
		}

		return $fileName;
	}

	public function getDeclaringClass(): ReflectionClass
	{
		return $this->reflection->getDeclaringClass();
	}

	public function getStartLine(): ?int
	{
		$line = $this->reflection->getStartLine();
		if ($line === false) {
			return null;
		}

		return $line;
	}

	public function getEndLine(): ?int
	{
		$line = $this->reflection->getEndLine();
		if ($line === false) {
			return null;
		}

		return $line;
	}

	public function getDocComment(): ?string
	{
		$docComment = $this->reflection->getDocComment();
		if ($docComment === false) {
			return null;
		}

		return $docComment;
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function isConstructor(): bool
	{
		return $this->reflection->isConstructor();
	}

	public function getPrototype(): BuiltinMethodReflection
	{
		return new self($this->reflection->getPrototype());
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isDeprecated());
	}

	public function isFinal(): bool
	{
		return $this->reflection->isFinal();
	}

	public function isInternal(): bool
	{
		return $this->reflection->isInternal();
	}

	public function isAbstract(): bool
	{
		return $this->reflection->isAbstract();
	}

	public function isVariadic(): bool
	{
		return $this->reflection->isVariadic();
	}

	public function getReturnType(): ?ReflectionType
	{
		return $this->reflection->getReturnType();
	}

	public function getTentativeReturnType(): ?ReflectionType
	{
		if (method_exists($this->reflection, 'getTentativeReturnType')) {
			return $this->reflection->getTentativeReturnType();
		}

		return null;
	}

	/**
	 * @return ReflectionParameter[]
	 */
	public function getParameters(): array
	{
		return $this->reflection->getParameters();
	}

}
