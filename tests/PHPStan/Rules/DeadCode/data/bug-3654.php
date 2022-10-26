<?php

namespace Bug3654;

class Foo implements \JsonSerializable
{

	/**
	 * @var int
	 */
	private $id;

	public function __construct(int $id)
	{
		$this->id = $id;
	}

	public function jsonSerialize(): array
	{
		return \get_object_vars($this);
	}
}

class Bar implements \JsonSerializable
{

	/**
	 * @var int
	 */
	private $id;

	public function __construct(int $id)
	{
		$this->id = $id;
	}

	public function jsonSerialize(): void
	{
		\array_walk($this, static function ($key, $value) {
		});
	}
}
