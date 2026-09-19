<?php declare(strict_types = 1);

namespace InvariantGenericComparison;

interface Animal {}
interface Cat extends Animal {}

/** @template T of object */
final class Box {}

/** @template T */
final class Bag {}

/**
 * @template T of object
 * @param Box<T> $b
 * @param Box<Cat> $c
 */
function compareTemplate(Box $b, Box $c): void
{
	if ($b === $c) {
		echo 'same';
	}
}

/**
 * @param Box<Animal> $b
 * @param Box<Cat> $c
 */
function compareRelated(Box $b, Box $c): void
{
	if ($b === $c) {
		echo 'same';
	}
}

/**
 * @param Bag<int> $b
 * @param Bag<string> $c
 */
function compareDisjoint(Bag $b, Bag $c): void
{
	if ($b === $c) {
		echo 'same';
	}
}
