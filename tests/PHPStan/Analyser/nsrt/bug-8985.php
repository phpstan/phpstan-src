<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8985;

use function PHPStan\Testing\assertType;

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

function () : void {
	assert((new Repository())->getAll() === []);

	$all = (new Repository())->getAll();
	assertType('array<int, Bug8985\Entity>', $all);
	$value = $all[0]->getValue();
};
