<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug8985;

use function PHPStan\Testing\assertType;

class Entity
{
	public string $value;

	public function __construct(string $value)
	{
		$this->value = $value;
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

	public string $name = 'default';

	/** @return array<int, Entity> */
	public static function staticGetAll(): array
	{
		return [new Entity('test')];
	}

	public function getEntity(): Entity
	{
		return new Entity('test');
	}
}

function testMethodCall(): void {
	assert((new Repository())->getAll() === []);

	$all = (new Repository())->getAll();
	assertType('array<int, Bug8985\Entity>', $all);
	$value = $all[0]->getValue();
}

function testNullsafeMethodCall(): void {
	assert((new Repository())?->getEntity()?->getValue() === 'specific');

	assertType('string', (new Repository())?->getEntity()?->getValue());
}

function testPropertyFetch(): void {
	assert((new Repository())->name === 'foo');

	assertType('string', (new Repository())->name);
}

function testNullsafePropertyFetch(): void {
	assert((new Repository())?->name === 'foo');

	assertType('string', (new Repository())?->name);
}

function testArrayDimFetch(): void {
	assert((new Repository())->getAll()[0]->getValue() === 'specific');

	assertType('string', (new Repository())->getAll()[0]->getValue());
}

function testStaticCall(): void {
	assert((new Repository())::staticGetAll() === []);

	assertType('array<int, Bug8985\Entity>', (new Repository())::staticGetAll());
}

function testChainedMethodCalls(): void {
	assert((new Repository())->getEntity()->getValue() === 'specific');

	assertType('string', (new Repository())->getEntity()->getValue());
}

function testChainedPropertyOnMethodCall(): void {
	assert((new Repository())->getEntity()->value === 'specific');

	assertType('string', (new Repository())->getEntity()->value);
}
