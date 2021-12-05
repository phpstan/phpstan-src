<?php declare(strict_types = 1);

namespace PHPStan\Cache;

class CacheItem
{

	private string $variableKey;

	/** @var mixed */
	private $data;

	/**
	 * @param mixed $data
	 */
	public function __construct(string $variableKey, $data)
	{
		$this->variableKey = $variableKey;
		$this->data = $data;
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
