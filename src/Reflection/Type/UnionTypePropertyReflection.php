<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function implode;

final class UnionTypePropertyReflection implements ExtendedPropertyReflection
{

	/**
	 * @param ExtendedPropertyReflection[] $properties
	 */
	public function __construct(private array $properties)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->properties[0]->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->isStatic());
	}

	public function isPrivate(): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->isPrivate());
	}

	public function isPublic(): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->isPublic());
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->properties, static fn (ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isDeprecated());
	}

	public function getDeprecatedDescription(): ?string
	{
		$descriptions = [];
		foreach ($this->properties as $property) {
			if (!$property->isDeprecated()->yes()) {
				continue;
			}
			$description = $property->getDeprecatedDescription();
			if ($description === null) {
				continue;
			}

			$descriptions[] = $description;
		}

		if (count($descriptions) === 0) {
			return null;
		}

		return implode(' ', $descriptions);
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->properties, static fn (ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isInternal());
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getReadableType(): Type
	{
		return TypeCombinator::union(...array_map(static fn (ExtendedPropertyReflection $property): Type => $property->getReadableType(), $this->properties));
	}

	public function getWritableType(): Type
	{
		return TypeCombinator::union(...array_map(static fn (ExtendedPropertyReflection $property): Type => $property->getWritableType(), $this->properties));
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->canChangeTypeAfterAssignment());
	}

	public function isReadable(): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->isReadable());
	}

	public function isWritable(): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->isWritable());
	}

	/**
	 * @param callable(ExtendedPropertyReflection): bool $cb
	 */
	private function computeResult(callable $cb): bool
	{
		$result = true;
		foreach ($this->properties as $property) {
			$result = $result && $cb($property);
		}

		return $result;
	}

	public function isAbstract(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->properties, static fn (ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isAbstract());
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->properties, static fn (ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isFinal());
	}

	public function isVirtual(): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->properties, static fn (ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isVirtual());
	}

	public function hasHook(string $hookType): bool
	{
		return $this->computeResult(static fn (ExtendedPropertyReflection $property) => $property->hasHook($hookType));
	}

	public function getHook(string $hookType): ExtendedMethodReflection
	{
		$hooks = [];
		foreach ($this->properties as $property) {
			if (!$property->hasHook($hookType)) {
				continue;
			}

			$hooks[] = $property->getHook($hookType);
		}

		if (count($hooks) === 0) {
			throw new ShouldNotHappenException();
		}

		if (count($hooks) === 1) {
			return $hooks[0];
		}

		return new UnionTypeMethodReflection($hooks[0]->getName(), $hooks);
	}

}
