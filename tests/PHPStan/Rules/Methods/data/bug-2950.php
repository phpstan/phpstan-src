<?php

namespace Bug2950;

class BlockFactory
{

	/** @var \ArrayObject<int, int> */
	private static $fullList = null;

	public static function isRegistered(int $id): bool
	{
		$b = self::$fullList[$id << 4];
		return $b !== null;
	}
}

class Attribute
{

	public function getId(): int
	{
		return 0;
	}

	public function isSyncable(): bool
	{
		return false;
	}

	public function isDesynchronized(): bool
	{
		return false;
	}

	public function getValue(): float
	{
		return 0.0;
	}

	public function setValue(float $f) : self
	{
		return $this;
	}
}



/**
 * @phpstan-implements \ArrayAccess<int, float>
 */
class AttributeMap implements \ArrayAccess
{

	/** @var Attribute[] */
	private $attributes = [];

	public function addAttribute(Attribute $attribute): void
	{
		$this->attributes[$attribute->getId()] = $attribute;
	}

	public function getAttribute(int $id): ?Attribute
	{
		return $this->attributes[$id] ?? null;
	}

	/**
	 * @return Attribute[]
	 */
	public function getAll(): array
	{
		return $this->attributes;
	}

	/**
	 * @return Attribute[]
	 */
	public function needSend(): array
	{
		return array_filter($this->attributes, function(Attribute $attribute){
			return $attribute->isSyncable() and $attribute->isDesynchronized();
		});
	}

	/**
	 * @param int $offset
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->attributes[$offset]);
	}

	/**
	 * @param int $offset
	 */
	public function offsetGet($offset): float
	{
		return $this->attributes[$offset]->getValue();
	}

	/**
	 * @param int|null   $offset
	 * @param float $value
	 */
	public function offsetSet($offset, $value): void
	{
		$this->attributes[$offset]->setValue($value);
	}

	/**
	 * @param int $offset
	 */
	public function offsetUnset($offset): void
	{
		throw new \RuntimeException("Could not unset an attribute from an attribute map");
	}

}
