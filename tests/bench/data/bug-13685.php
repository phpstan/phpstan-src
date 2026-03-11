<?php

namespace Bug13685;

/**
 * @template TAttr of array<string, mixed>
 */
trait AttributeTrait
{
	/**
	 * @param key-of<TAttr> $key
	 */
	public function hasAttribute(string $key): bool
	{
		$attr = $this->getAttributes();

		return isset($attr[$key]);
	}

	/**
	 * @template TKey of key-of<TAttr>
	 * @param TKey&key-of<TAttr> $key
	 * @param TAttr[TKey] $val
	 */
	public function setAttribute(string $key, $val): self
	{
		$attr = $this->getAttributes();
		$attr[$key] = $val;
		/** @phpstan-ignore-next-line */
		$this->setAttributes($attr);

		return $this;
	}

	/**
	 * @template TKey of key-of<TAttr>
	 * @param TKey $key
	 * @return TAttr[TKey]|null
	 */
	public function getAttribute(string $key)
	{
		return $this->getAttributes()[$key] ?? null;
	}

	/**
	 * @param key-of<TAttr> $key
	 */
	public function unsetAttribute(string $key): self
	{
		$attr = $this->getAttributes();
		unset($attr[$key]);
		$this->setAttributes($attr);

		return $this;
	}

	/**
	 * @param TAttr $attributes
	 * @return static
	 */
	abstract public function setAttributes(array $attributes): self;

	/**
	 * @return TAttr
	 */
	abstract public function getAttributes(): array;
}

/**
 * @phpstan-type FooAttributes array<self::ATTR_*, mixed>
 */
class FooWithAttribute
{
	public const ATTR_ONE = 'attr_one';
	public const ATTR_TWO = 'attr_two';
	public const ATTR_THREE = 'attr_three';

	/** @var FooAttributes */
	private array $attributes = [];

	/** @use AttributeTrait<FooAttributes> */
	use AttributeTrait;

	public function setAttributes(array $attributes): self
	{
		return $this;
	}

	/**
	 * @phpstan-return FooAttributes
	 */
	public function getAttributes(): array
	{
		return $this->attributes ?? [];
	}
}
