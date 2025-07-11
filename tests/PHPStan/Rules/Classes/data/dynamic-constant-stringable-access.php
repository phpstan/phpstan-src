<?php  // lint >= 8.3

namespace ClassConstantDynamicStringableAccess;

use Stringable;
use DateTime;
use DateTimeImmutable;

abstract class Foo
{

	public function test(mixed $mixed, ?string $nullableStr, ?Stringable $nullableStringable, int $int, ?int $nullableInt, DateTime|string $datetimeOrStr, Stringable $stringable): void
	{
		echo self::{$mixed};
		echo self::{$nullableStr};
		echo self::{$nullableStringable};
		echo self::{$int};
		echo self::{$nullableInt};
		echo self::{$datetimeOrStr};
		echo self::{1111};
		echo self::{(string)$stringable};
		echo self::{$stringable}; // Uncast Stringable objects will cause a runtime error
	}

}

final class Bar extends Foo
{

	public function test(mixed $mixed, ?string $nullableStr, ?Stringable $nullableStringable, int $int, ?int $nullableInt, DateTime|string $datetimeOrStr, Stringable $stringable): void
	{
		echo parent::{$mixed};
		echo self::{$mixed};
	}

	public function testClassDynamic(DateTime|DateTimeImmutable $datetime, object $obj, mixed $mixed): void
	{
		echo $datetime::{$mixed};
		echo $obj::{$mixed};
	}

}
