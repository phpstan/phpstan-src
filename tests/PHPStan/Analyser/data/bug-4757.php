<?php // lint >= 8.0

namespace Bug4757;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFoo()) {
			assertType(Reservation::class, $oldReservation);
			assertType('true', $oldReservation->isFoo());
			return;
		}

		assertType(Reservation::class . '|null', $oldReservation);
	}

	public function sayHello2(?Reservation $oldReservation): void
	{
		if (!$oldReservation?->isFoo()) {
			assertType(Reservation::class . '|null', $oldReservation);
			assertType('bool', $oldReservation->isFoo());
			return;
		}

		assertType(Reservation::class, $oldReservation);
		assertType('true', $oldReservation->isFoo());
	}

	public function sayHello3(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFoo() === true) {
			assertType(Reservation::class, $oldReservation);
			assertType('true', $oldReservation->isFoo());
			return;
		}

		assertType(Reservation::class . '|null', $oldReservation);
		assertType('bool', $oldReservation->isFoo());
	}

	public function sayHello4(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFoo() === false) {
			assertType(Reservation::class , $oldReservation);
			assertType('false', $oldReservation->isFoo());
			return;
		}

		//assertType(Reservation::class . '|null', $oldReservation);
		assertType('bool', $oldReservation->isFoo());
	}

	public function sayHello5(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFoo() === null) {
			assertType(Reservation::class . '|null', $oldReservation);
			return;
		}

		assertType(Reservation::class, $oldReservation);
	}

	public function sayHello6(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFoo() !== null) {
			assertType(Reservation::class, $oldReservation);
			assertType('bool', $oldReservation->isFoo());
			return;
		}

		assertType(Reservation::class . '|null', $oldReservation);
		assertType('bool', $oldReservation->isFoo());
	}

	public function sayHelloImpure(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFooImpure()) {
			assertType(Reservation::class, $oldReservation);
			assertType('bool', $oldReservation->isFooImpure());
			return;
		}

		assertType(Reservation::class . '|null', $oldReservation);
	}

	public function sayHello2Impure(?Reservation $oldReservation): void
	{
		if (!$oldReservation?->isFooImpure()) {
			assertType(Reservation::class . '|null', $oldReservation);
			return;
		}

		assertType(Reservation::class, $oldReservation);
	}

	public function sayHello3Impure(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFooImpure() === true) {
			assertType(Reservation::class, $oldReservation);
			return;
		}

		assertType(Reservation::class . '|null', $oldReservation);
	}

	public function sayHello4Impure(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFooImpure() === false) {
			assertType(Reservation::class , $oldReservation);
			return;
		}

		//assertType(Reservation::class . '|null', $oldReservation);
	}

	public function sayHello5Impure(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFooImpure() === null) {
			assertType(Reservation::class . '|null', $oldReservation);
			return;
		}

		assertType(Reservation::class, $oldReservation);
	}

	public function sayHello6Impure(?Reservation $oldReservation): void
	{
		if ($oldReservation?->isFooImpure() !== null) {
			assertType(Reservation::class, $oldReservation);
			return;
		}

		assertType(Reservation::class . '|null', $oldReservation);
	}
}

interface Reservation {
	public function isFoo(): bool;

	/** @phpstan-impure */
	public function isFooImpure(): bool;
}

interface Bar
{
	public function get(): ?int;

	/** @phpstan-impure */
	public function getImpure(): ?int;
}

class Foo
{

	public function getBarOrNull(): ?Bar
	{
		return null;
	}

	public function doFoo(Bar $b): void
	{
		$barOrNull = $this->getBarOrNull();
		if ($barOrNull?->get() === null) {
			assertType(Bar::class . '|null', $barOrNull);
			assertType('int|null', $barOrNull->get());
			//assertType('null', $barOrNull?->get());
			return;
		}

		assertType(Bar::class, $barOrNull);
		assertType('int', $barOrNull->get());
	}

	public function doFooImpire(Bar $b): void
	{
		$barOrNull = $this->getBarOrNull();
		if ($barOrNull?->getImpure() === null) {
			assertType(Bar::class . '|null', $barOrNull);
			assertType('int|null', $barOrNull->getImpure());
			assertType('int|null', $barOrNull?->getImpure());
			return;
		}

		assertType(Bar::class, $barOrNull);
		assertType('int|null', $barOrNull->getImpure());
	}

	public function doFoo2(Bar $b): void
	{
		$barOrNull = $this->getBarOrNull();
		if ($barOrNull?->get() !== null) {
			assertType(Bar::class, $barOrNull);
			assertType('int', $barOrNull->get());
			return;
		}

		assertType(Bar::class . '|null', $barOrNull);
		assertType('int|null', $barOrNull->get());
	}

	public function doFoo2Impure(Bar $b): void
	{
		$barOrNull = $this->getBarOrNull();
		if ($barOrNull?->getImpure() !== null) {
			assertType(Bar::class, $barOrNull);
			assertType('int|null', $barOrNull->getImpure());
			return;
		}

		assertType(Bar::class . '|null', $barOrNull);
		assertType('int|null', $barOrNull->getImpure());
	}

	public function doFoo3(Bar $b): void
	{
		$barOrNull = $this->getBarOrNull();
		if ($barOrNull?->get()) {
			assertType(Bar::class, $barOrNull);
			assertType('int<min, -1>|int<1, max>', $barOrNull->get());
			return;
		}

		assertType(Bar::class . '|null', $barOrNull);
		assertType('int|null', $barOrNull->get());
	}

	public function doFoo3Impure(Bar $b): void
	{
		$barOrNull = $this->getBarOrNull();
		if ($barOrNull?->getImpure()) {
			assertType(Bar::class, $barOrNull);
			assertType('int|null', $barOrNull->getImpure());
			return;
		}

		assertType(Bar::class . '|null', $barOrNull);
		assertType('int|null', $barOrNull->getImpure());
	}

}
