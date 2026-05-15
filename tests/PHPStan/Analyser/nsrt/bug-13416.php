<?php

declare(strict_types = 1);

namespace Bug13416;

use function PHPStan\Testing\assertType;

class MyRecord
{
	/** @var list<self> */
	private static array $storage = [];

	/**
	 * @return list<self>
	 * @phpstan-impure
	 */
	public static function find(): array
	{
		return self::$storage;
	}

	/** @phpstan-impure */
	public function insert(): void
	{
		self::$storage[] = $this;
	}

	/**
	 * @return non-empty-string
	 * @phpstan-impure
	 */
	public function getName(): string
	{
		return 'test';
	}
}

class Repository
{
	/**
	 * @return list<MyRecord>
	 * @phpstan-impure
	 */
	public function findAll(): array
	{
		return [];
	}

	/** @phpstan-impure */
	public function save(MyRecord $record): void
	{
	}
}

function testImpureStaticCallNotNarrowedByCount(): void
{
	assert(count(MyRecord::find()) === 1);
	// Impure call result should not be narrowed
	assertType('int<0, max>', count(MyRecord::find()));
}

function testImpureMethodCallNotNarrowedByCount(): void
{
	$repo = new Repository();

	assert(count($repo->findAll()) === 1);
	// Impure call result should not be narrowed
	assertType('int<0, max>', count($repo->findAll()));
}

function testStrlenOfImpureCallNotNarrowed(): void
{
	$record = new MyRecord();

	assert(strlen($record->getName()) === 3);
	// strlen wrapping an impure call should not be narrowed
	assertType('int<1, max>', strlen($record->getName()));
}

function testPureFunctionStaysNarrowed(): void
{
	/** @var list<int> $arr */
	$arr = [1];
	assert(count($arr) === 1);
	assertType('1', count($arr));

	$x = rand(0, 10);

	// Pure expressions stay narrowed
	assertType('1', count($arr));
}
