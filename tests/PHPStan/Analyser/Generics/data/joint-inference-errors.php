<?php declare(strict_types = 1);

namespace JointInference;

require_once __DIR__ . '/joint-inference.php';

/**
 * @param Promise<1> $one
 * @param Promise<2> $two
 */
function fixedPromises(Promise $one, Promise $two): void
{
	all([$one, $two]);
}

/**
 * @param Box<Cat> $cat
 * @param Box<Dog> $dog
 */
function fixedBoxes(Box $cat, Box $dog): void
{
	both($cat, $dog);
}

/** @param Resolver<int> $resolver */
function requireIntResolver(Resolver $resolver): void
{
}

function incompatibleWrite(): void
{
	$one = new Resolver();
	$two = new Resolver();
	all([$one->getPromise(), $two->getPromise()]);
	requireIntResolver($one);
	$one->resolve(1);
	$two->resolve('wrong');
}

function incompatibleCallback(): void
{
	$one = new Resolver();
	$two = new Resolver();
	$combined = all([$one->getPromise(), $two->getPromise()]);
	$one->resolve(1);
	$two->resolve(2);
	$combined->onCompletion(static function (string $value): void {});
}

/** @param EventCollection<WrittenEvent<'one'>|WrittenEvent<'two'>> $events */
function fixedEvents(EventCollection $events): void
{
	new ContainerEvent($events);
}

function incompatibleEvent(): void
{
	new ContainerEvent(new EventCollection([new WrittenEvent('one'), new Event()]));
}

/**
 * @template T of int
 * @param array<Promise<T>> $promises
 */
function allIntegers(array $promises): void
{
}

function incompatibleBound(): void
{
	$one = new Resolver();
	$two = new Resolver();
	allIntegers([$one->getPromise(), $two->getPromise()]);
	$one->resolve(1);
	$two->resolve('wrong');
}

function incompatibleRead(): void
{
	$one = new Resolver();
	$two = new Resolver();
	$one->getPromise()->onCompletion(static function (int $value): void {});
	all([$one->getPromise(), $two->getPromise()]);
	$one->resolve(1);
	$two->resolve('wrong');
}
