<?php declare(strict_types = 1);

namespace PHPStan\Cache;

final class CacheItem
{

	/**
	 * @param mixed $data
	 */
	public function __construct(private string $variableKey, private $data)
	{
	}

	public function isVariableKeyValid(string $variableKey): bool
	{
		return $this->variableKey === $variableKey;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['variableKey'], $properties['data']);
	}

}
