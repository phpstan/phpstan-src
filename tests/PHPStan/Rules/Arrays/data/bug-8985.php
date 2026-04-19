<?php

declare(strict_types=1);

namespace Bug8985c;

class Entity
{
	public function __construct(private string $value)
	{
	}

	public function getValue(): string
	{
		return $this->value;
	}
}

class Repository
{
	/** @return array<int, Entity> */
	public function getAll(): array
	{
		return [new Entity('test')];
	}
}

assert((new Repository())->getAll() === []);

$all = (new Repository())->getAll();
$value = $all[0]->getValue();
