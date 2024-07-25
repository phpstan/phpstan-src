<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function count;

/**
 * @api
 * @final
 */
class TemplateTypeMap
{

	private static ?TemplateTypeMap $empty = null;

	private ?TemplateTypeMap $resolvedToBounds = null;

	/**
	 * @api
	 * @param array<string, Type> $types
	 * @param array<string, Type> $lowerBoundTypes
	 */
	public function __construct(private array $types, private array $lowerBoundTypes = [])
	{
	}

	public function convertToLowerBoundTypes(): self
	{
		$lowerBoundTypes = $this->types;
		foreach ($this->lowerBoundTypes as $name => $type) {
			if (isset($lowerBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($lowerBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					continue;
				}
				$lowerBoundTypes[$name] = $intersection;
			} else {
				$lowerBoundTypes[$name] = $type;
			}
		}

		return new self([], $lowerBoundTypes);
	}

	public static function createEmpty(): self
	{
		$empty = self::$empty;

		if ($empty !== null) {
			return $empty;
		}

		$empty = new self([], []);
		self::$empty = $empty;

		return $empty;
	}

	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	public function count(): int
	{
		return count($this->types + $this->lowerBoundTypes);
	}

	/** @return array<string, Type> */
	public function getTypes(): array
	{
		$types = $this->types;
		foreach ($this->lowerBoundTypes as $name => $type) {
			if (array_key_exists($name, $types)) {
				continue;
			}

			$types[$name] = $type;
		}

		return $types;
	}

	public function hasType(string $name): bool
	{
		return array_key_exists($name, $this->getTypes());
	}

	public function getType(string $name): ?Type
	{
		return $this->getTypes()[$name] ?? null;
	}

	public function unsetType(string $name): self
	{
		if (!$this->hasType($name)) {
			return $this;
		}

		$types = $this->types;
		$lowerBoundTypes = $this->lowerBoundTypes;

		unset($types[$name]);
		unset($lowerBoundTypes[$name]);

		if (count($types) === 0 && count($lowerBoundTypes) === 0) {
			return self::createEmpty();
		}

		return new self($types, $lowerBoundTypes);
	}

	public function union(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeCombinator::union($result[$name], $type);
			} else {
				$result[$name] = $type;
			}
		}

		$resultLowerBoundTypes = $this->lowerBoundTypes;
		foreach ($other->lowerBoundTypes as $name => $type) {
			if (isset($resultLowerBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($resultLowerBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					continue;
				}
				$resultLowerBoundTypes[$name] = $intersection;
			} else {
				$resultLowerBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultLowerBoundTypes);
	}

	public function benevolentUnion(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeUtils::toBenevolentUnion(TypeCombinator::union($result[$name], $type));
			} else {
				$result[$name] = $type;
			}
		}

		$resultLowerBoundTypes = $this->lowerBoundTypes;
		foreach ($other->lowerBoundTypes as $name => $type) {
			if (isset($resultLowerBoundTypes[$name])) {
				$intersection = TypeCombinator::intersect($resultLowerBoundTypes[$name], $type);
				if ($intersection instanceof NeverType) {
					continue;
				}
				$resultLowerBoundTypes[$name] = $intersection;
			} else {
				$resultLowerBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultLowerBoundTypes);
	}

	public function intersect(self $other): self
	{
		$result = $this->types;

		foreach ($other->types as $name => $type) {
			if (isset($result[$name])) {
				$result[$name] = TypeCombinator::intersect($result[$name], $type);
			} else {
				$result[$name] = $type;
			}
		}

		$resultLowerBoundTypes = $this->lowerBoundTypes;
		foreach ($other->lowerBoundTypes as $name => $type) {
			if (isset($resultLowerBoundTypes[$name])) {
				$resultLowerBoundTypes[$name] = TypeCombinator::union($resultLowerBoundTypes[$name], $type);
			} else {
				$resultLowerBoundTypes[$name] = $type;
			}
		}

		return new self($result, $resultLowerBoundTypes);
	}

	/** @param callable(string,Type):Type $cb */
	public function map(callable $cb): self
	{
		$types = [];
		foreach ($this->getTypes() as $name => $type) {
			$types[$name] = $cb($name, $type);
		}

		return new self($types);
	}

	public function resolveToBounds(): self
	{
		if ($this->resolvedToBounds !== null) {
			return $this->resolvedToBounds;
		}
		return $this->resolvedToBounds = $this->map(static fn (string $name, Type $type): Type => TemplateTypeHelper::resolveToBounds($type));
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['types'],
			$properties['lowerBoundTypes'] ?? [],
		);
	}

}
