<?php // lint >= 8.0

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

	/** @return array<int, Entity> */
	public function getAllFor(mixed $filter): array
	{
		return [new Entity('test')];
	}

	/** @phpstan-impure */
	public static function create(): self
	{
		return new self();
	}
}

assert((new Repository())->getAll() === []);

$all = (new Repository())->getAll();
$value = $all[0]->getValue();

assert(Repository::create()->getAll() === []);

$all2 = Repository::create()->getAll();
$value2 = $all2[0]->getValue();

function testImpureArgument(Repository $repository): void {
	assert($repository->getAllFor(Repository::create()) === []);

	$all = $repository->getAllFor(Repository::create());
	$value = $all[0]->getValue();
}
