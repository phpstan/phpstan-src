<?php declare(strict_types = 1);

namespace InvariantGenericAssertPhpDoc;

interface Animal {}
interface Cat extends Animal {}

/** @template T of object */
final class Box {}

/** @template T */
final class Bag {}

/**
 * @template T of object
 * @param Box<T> $b
 * @phpstan-assert Box<T&Cat> $b
 */
function assertCat(Box $b): void {}

/**
 * @param Box<Animal> $b
 * @phpstan-assert Box<Cat> $b
 */
function assertCatOnAnimalBox(Box $b): void {}

/**
 * @param Bag<int> $b
 * @phpstan-assert Bag<string> $b
 */
function assertStringBagOnIntBag(Bag $b): void {}

final class Asserter
{

	/**
	 * @template T of object
	 * @param Box<T> $b
	 * @phpstan-assert Box<T&Cat> $b
	 */
	public function assertCat(Box $b): void {}

	/**
	 * @param Box<Animal> $b
	 * @phpstan-assert Box<Cat> $b
	 */
	public function assertCatOnAnimalBox(Box $b): void {}

	/**
	 * @param Bag<int> $b
	 * @phpstan-assert Bag<string> $b
	 */
	public static function assertStringBagOnIntBag(Bag $b): void {}

}
