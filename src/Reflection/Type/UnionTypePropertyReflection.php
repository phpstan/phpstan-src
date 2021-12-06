<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function implode;

class UnionTypePropertyReflection implements PropertyReflection
{

	/** @var PropertyReflection[] */
	private array $properties;

	/**
	 * @param PropertyReflection[] $properties
	 */
	public function __construct(array $properties)
	{
		$this->properties = $properties;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->properties[0]->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		foreach ($this->properties as $property) {
			if (!$property->isStatic()) {
				return false;
			}
		}

		return true;
	}

	public function isPrivate(): bool
	{
		foreach ($this->properties as $property) {
			if ($property->isPrivate()) {
				return true;
			}
		}

		return false;
	}

	public function isPublic(): bool
	{
		foreach ($this->properties as $property) {
			if (!$property->isPublic()) {
				return false;
			}
		}

		return true;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::extremeIdentity(...array_map(static function (PropertyReflection $property): TrinaryLogic {
			return $property->isDeprecated();
		}, $this->properties));
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
		return TrinaryLogic::extremeIdentity(...array_map(static function (PropertyReflection $property): TrinaryLogic {
			return $property->isInternal();
		}, $this->properties));
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getReadableType(): Type
	{
		return TypeCombinator::union(...array_map(static function (PropertyReflection $property): Type {
			return $property->getReadableType();
		}, $this->properties));
	}

	public function getWritableType(): Type
	{
		return TypeCombinator::union(...array_map(static function (PropertyReflection $property): Type {
			return $property->getWritableType();
		}, $this->properties));
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		foreach ($this->properties as $property) {
			if (!$property->canChangeTypeAfterAssignment()) {
				return false;
			}
		}

		return true;
	}

	public function isReadable(): bool
	{
		foreach ($this->properties as $property) {
			if (!$property->isReadable()) {
				return false;
			}
		}

		return true;
	}

	public function isWritable(): bool
	{
		foreach ($this->properties as $property) {
			if (!$property->isWritable()) {
				return false;
			}
		}

		return true;
	}

}
