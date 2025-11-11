<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function sprintf;

/**
 * @api
 */
final class PhpPropertyReflection implements ExtendedPropertyReflection
{

	private ?Type $readableType = null;

	private ?Type $writableType = null;

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	public function __construct(
		private ClassReflection $declaringClass,
		private ?ClassReflection $declaringTrait,
		private Type $nativeType,
		private ?Type $readablePhpDocType,
		private ?Type $writablePhpDocType,
		private ReflectionProperty $reflection,
		private ?ExtendedMethodReflection $getHook,
		private ?ExtendedMethodReflection $setHook,
		private ?string $deprecatedDescription,
		private bool $isDeprecated,
		private bool $isInternal,
		private bool $isReadOnlyByPhpDoc,
		private bool $isAllowedPrivateMutation,
		private array $attributes,
		private bool $isFinal,
		private bool $readable,
		private bool $writable,
		private bool $private,
		private bool $public,
	)
	{
	}

	public function getName(): string
	{
		return $this->reflection->getName();
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
		return $this->private;
	}

	public function isPublic(): bool
	{
		return $this->public;
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
		return $this->readableType ??= TypehintHelper::decideType(
			$this->nativeType,
			$this->readablePhpDocType,
		);
	}

	public function getWritableType(): Type
	{
		if ($this->hasHook('set')) {
			$setHookVariant = $this->getHook('set')->getOnlyVariant();
			$parameters = $setHookVariant->getParameters();
			if (isset($parameters[0])) {
				return $parameters[0]->getType();
			}
		}

		if ($this->writableType !== null) {
			return $this->writableType;
		}

		if ($this->writablePhpDocType === null || !$this->writablePhpDocType->isNever()->no()) {
			return $this->writableType = TypehintHelper::decideType(
				$this->nativeType,
				$this->readablePhpDocType,
			);
		}

		if (
			$this->readablePhpDocType !== null
			&& !$this->readablePhpDocType->equals($this->writablePhpDocType)
		) {
			return $this->writableType = $this->writablePhpDocType;
		}

		return $this->writableType = TypehintHelper::decideType(
			$this->nativeType,
			$this->writablePhpDocType,
		);
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		if ($this->isStatic()) {
			return $this->getReadableType()->equals($this->getWritableType());
		}

		if ($this->isVirtual()->yes()) {
			return false;
		}

		if ($this->hasHook('get')) {
			return false;
		}

		if ($this->hasHook('set')) {
			return false;
		}

		return $this->getReadableType()->equals($this->getWritableType());
	}

	public function isPromoted(): bool
	{
		return $this->reflection->isPromoted();
	}

	public function hasPhpDocType(): bool
	{
		return $this->readablePhpDocType !== null;
	}

	public function getPhpDocType(): Type
	{
		if ($this->readablePhpDocType !== null) {
			return $this->readablePhpDocType;
		}

		return new MixedType();
	}

	public function hasNativeType(): bool
	{
		return !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
	}

	public function getNativeType(): Type
	{
		return $this->nativeType;
	}

	public function isReadable(): bool
	{
		if (!$this->readable) {
			return false;
		}

		if ($this->isStatic()) {
			return true;
		}

		if (!$this->isVirtual()->yes()) {
			return true;
		}

		return $this->hasHook('get');
	}

	public function isWritable(): bool
	{
		if (!$this->writable) {
			return false;
		}

		if ($this->isStatic()) {
			return true;
		}

		if (!$this->isVirtual()->yes()) {
			return true;
		}

		return $this->hasHook('set');
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

	public function isAbstract(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isAbstract());
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isFinal());
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isFinal);
	}

	public function isVirtual(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isVirtual());
	}

	public function hasHook(string $hookType): bool
	{
		if ($hookType === 'get') {
			return $this->getHook !== null;
		}

		return $this->setHook !== null;
	}

	public function isHooked(): bool
	{
		return $this->getHook !== null || $this->setHook !== null;
	}

	public function getHook(string $hookType): ExtendedMethodReflection
	{
		if ($hookType === 'get') {
			if ($this->getHook === null) {
				throw new MissingMethodFromReflectionException($this->declaringClass->getName(), sprintf('$%s::get', $this->reflection->getName()));
			}

			return $this->getHook;
		}

		if ($this->setHook === null) {
			throw new MissingMethodFromReflectionException($this->declaringClass->getName(), sprintf('$%s::set', $this->reflection->getName()));
		}

		return $this->setHook;
	}

	public function isProtectedSet(): bool
	{
		return $this->reflection->isProtectedSet();
	}

	public function isPrivateSet(): bool
	{
		return $this->reflection->isPrivateSet();
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function isDummy(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
