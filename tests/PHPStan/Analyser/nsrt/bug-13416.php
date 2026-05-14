<?php

declare(strict_types = 1);

namespace Bug13416;

use function PHPStan\Testing\assertType;

class MyRecord
{
	/** @return list<self> */
	public static function find(): array
	{
		return [];
	}

	public function insert(): void
	{
	}

	/** @return non-empty-string */
	public function getName(): string
	{
		return 'test';
	}
}

class Repository
{
	/** @return list<MyRecord> */
	public function findAll(): array
	{
		return [];
	}

	public function save(MyRecord $record): void
	{
	}
}

function testStaticCallInvalidatedByMethodCall(): void
{
	assert(count(MyRecord::find()) === 1);
	assertType('1', count(MyRecord::find()));

	$msg2 = new MyRecord();
	$msg2->insert();

	assertType('int<0, max>', count(MyRecord::find()));
}

function testMethodCallInvalidatedByMethodCall(): void
{
	$repo = new Repository();

	assert(count($repo->findAll()) === 1);
	assertType('1', count($repo->findAll()));

	$msg2 = new MyRecord();
	$msg2->insert();

	assertType('int<0, max>', count($repo->findAll()));
}

function testStrlenOfImpureCall(): void
{
	$record = new MyRecord();

	assert(strlen($record->getName()) === 3);
	assertType('3', strlen($record->getName()));

	$msg2 = new MyRecord();
	$msg2->insert();

	assertType('int<1, max>', strlen($record->getName()));
}

function testCountNotInvalidatedByPureFunction(): void
{
	assert(count(MyRecord::find()) === 1);
	assertType('1', count(MyRecord::find()));

	$x = rand(0, 10);

	assertType('1', count(MyRecord::find()));
}

class ServiceWithImpureCall
{
	public function testMethodCallInvalidation(): void
	{
		$repo = new Repository();

		assert(count($repo->findAll()) === 1);
		assertType('1', count($repo->findAll()));

		$msg2 = new MyRecord();
		$msg2->insert();

		assertType('int<0, max>', count($repo->findAll()));
	}

	public function testStaticCallInvalidation(): void
	{
		assert(count(MyRecord::find()) === 1);
		assertType('1', count(MyRecord::find()));

		$msg2 = new MyRecord();
		$msg2->insert();

		assertType('int<0, max>', count(MyRecord::find()));
	}
}
