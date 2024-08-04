<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;

/**
 * @api
 * @final
 */
class PhpPropertyReflection implements ExtendedPropertyReflection
{

	private ?Type $finalNativeType = null;

	private ?Type $type = null;

	public function __construct(
		private ClassReflection $declaringClass,
		private ?ClassReflection $declaringTrait,
		private ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null $nativeType,
		private ?Type $phpDocType,
		private ReflectionProperty $reflection,
		private ?string $deprecatedDescription,
		private bool $isDeprecated,
		private bool $isInternal,
		private bool $isReadOnlyByPhpDoc,
		private bool $isAllowedPrivateMutation,
	)
	{
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
		return $this->reflection->isReadOnly();
	}

	public function isReadOnlyByPhpDoc(): bool
	{
		return $this->isReadOnlyByPhpDoc;
	}

	public function getReadableType(): Type
	{
		if ($this->type === null) {
			$this->type = TypehintHelper::decideTypeFromReflection(
				$this->nativeType,
				$this->phpDocType,
				$this->declaringClass,
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
				$this->declaringClass,
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

	public function isAllowedPrivateMutation(): bool
	{
		return $this->isAllowedPrivateMutation;
	}

	public function getNativeReflection(): ReflectionProperty
	{
		return $this->reflection;
	}

}
