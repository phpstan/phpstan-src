<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use ReflectionProperty;
use ReflectionType;
use function method_exists;

/** @api */
class PhpPropertyReflection implements PropertyReflection
{

	private ClassReflection $declaringClass;

	private ?ClassReflection $declaringTrait;

	private ?ReflectionType $nativeType;

	private ?Type $finalNativeType = null;

	private ?Type $phpDocType;

	private ?Type $type = null;

	private ReflectionProperty $reflection;

	private ?string $deprecatedDescription;

	private bool $isDeprecated;

	private bool $isInternal;

	public function __construct(
		ClassReflection $declaringClass,
		?ClassReflection $declaringTrait,
		?ReflectionType $nativeType,
		?Type $phpDocType,
		ReflectionProperty $reflection,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal,
	)
	{
		$this->declaringClass = $declaringClass;
		$this->declaringTrait = $declaringTrait;
		$this->nativeType = $nativeType;
		$this->phpDocType = $phpDocType;
		$this->reflection = $reflection;
		$this->deprecatedDescription = $deprecatedDescription;
		$this->isDeprecated = $isDeprecated;
		$this->isInternal = $isInternal;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getDeclaringTrait(): ?ClassReflection
	{
		return $this->declaringTrait;
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

	public function isReadOnly(): bool
	{
		if (method_exists($this->reflection, 'isReadOnly')) {
			return $this->reflection->isReadOnly();
		}

		return false;
	}

	public function getReadableType(): Type
	{
		if ($this->type === null) {
			$this->type = TypehintHelper::decideTypeFromReflection(
				$this->nativeType,
				$this->phpDocType,
				$this->declaringClass->getName(),
			);
		}

		return $this->type;
	}

	public function getWritableType(): Type
	{
		return $this->getReadableType();
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return true;
	}

	public function isPromoted(): bool
	{
		if (!method_exists($this->reflection, 'isPromoted')) {
			return false;
		}

		return $this->reflection->isPromoted();
	}

	public function hasPhpDocType(): bool
	{
		return $this->phpDocType !== null;
	}

	public function getPhpDocType(): Type
	{
		if ($this->phpDocType !== null) {
			return $this->phpDocType;
		}

		return new MixedType();
	}

	public function hasNativeType(): bool
	{
		return $this->nativeType !== null;
	}

	public function getNativeType(): Type
	{
		if ($this->finalNativeType === null) {
			$this->finalNativeType = TypehintHelper::decideTypeFromReflection(
				$this->nativeType,
				null,
				$this->declaringClass->getName(),
			);
		}

		return $this->finalNativeType;
	}

	public function isReadable(): bool
	{
		return true;
	}

	public function isWritable(): bool
	{
		return true;
	}

	public function getDeprecatedDescription(): ?string
	{
		if ($this->isDeprecated) {
			return $this->deprecatedDescription;
		}

		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isDeprecated);
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isInternal);
	}

	public function getNativeReflection(): ReflectionProperty
	{
		return $this->reflection;
	}

}
