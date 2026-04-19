<?php declare(strict_types = 1);

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
	/** @var array<int, Entity> */
	public array $all = [];

	/** @return array<int, Entity> */
	public function getAll(): array
	{
		return [new Entity('test')];
	}

	public function getFirst(): ?Entity
	{
		return $this->all[0] ?? null;
	}
}

// Method call on new - original bug report
function testAssertOnNewThenNew(): void
{
	assert((new Repository())->getAll() === []);

	$all = (new Repository())->getAll();
	assertType('array<int, Bug8985\Entity>', $all);
}

function testAssignAssertThenNew(): void
{
	$all = (new Repository())->getAll();
	assert($all === []);
	assertType('array{}', $all);

	$all = (new Repository())->getAll();
	assertType('array<int, Bug8985\Entity>', $all);
}

// Property access on new
function testPropertyAccessOnNew(): void
{
	assert((new Repository())->all === []);

	$all = (new Repository())->all;
	assertType('array<int, Bug8985\Entity>', $all);
}

// Nullsafe method call on new
function testNullsafeMethodOnNew(): void
{
	assert((new Repository())->getFirst()?->getValue() === null);

	$value = (new Repository())->getFirst()?->getValue();
	assertType('string|null', $value);
}

// Chained method call on new
class Builder
{
	/** @return array<int, string> */
	public function build(): array
	{
		return ['a', 'b'];
	}
}

class BuilderFactory
{
	public function create(): Builder
	{
		return new Builder();
	}
}

function testChainedMethodOnNew(): void
{
	assert((new BuilderFactory())->create()->build() === []);

	$result = (new BuilderFactory())->create()->build();
	assertType('array<int, string>', $result);
}

// Array dim fetch on method call on new
function testArrayDimFetchOnNew(): void
{
	$items = (new Repository())->getAll();
	assert(count($items) === 0);

	$items2 = (new Repository())->getAll();
	assertType('array<int, Bug8985\Entity>', $items2);
}

// Clone expression - also creates a fresh object
function testCloneExpression(Repository $repo): void
{
	assert((clone $repo)->getAll() === []);

	$all = (clone $repo)->getAll();
	assertType('array<int, Bug8985\Entity>', $all);
}
