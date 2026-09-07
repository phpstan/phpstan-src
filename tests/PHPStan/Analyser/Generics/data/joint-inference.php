<?php declare(strict_types = 1);

namespace JointInference;

use Closure;
use LogicException;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @template T */
class Promise
{

	/** @param Closure(T): void $callback */
	public function onCompletion(Closure $callback): void
	{
	}

}

/** @template T */
class Resolver
{

	/** @return Promise<T> */
	public function getPromise(): Promise
	{
		throw new LogicException();
	}

	/** @param T $value */
	public function resolve($value): void
	{
	}

}

/**
 * @template T
 * @param array<Promise<T>> $promises
 * @return Promise<array<T>>
 */
function all(array $promises): Promise
{
	throw new LogicException();
}

function resolveAfterCombining(): void
{
	$one = new Resolver();
	$two = new Resolver();
	$combined = all([$one->getPromise(), $two->getPromise()]);
	$one->resolve(1);
	$two->resolve(2);
	assertType('JointInference\\Resolver<1|2>', $one);
	assertType('JointInference\\Resolver<1|2>', $two);
	assertType('JointInference\\Promise<array<1|2>>', $combined);
	assertNativeType('JointInference\\Resolver<mixed>', $one);
}

function resolveBeforeCombining(): void
{
	$one = new Resolver();
	$two = new Resolver();
	$one->resolve(1);
	$two->resolve(2);
	$combined = all([$two->getPromise(), $one->getPromise()]);
	assertType('JointInference\\Resolver<1|2>', $one);
	assertType('JointInference\\Resolver<1|2>', $two);
	assertType('JointInference\\Promise<array<1|2>>', $combined);
}

class Animal
{
}

class Cat extends Animal
{
}

class Dog extends Animal
{
}

/** @template T */
class Box
{

	/** @var T */
	private $value;

	/** @param T $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/** @param T $value */
	public function set($value): void
	{
		$this->value = $value;
	}

	/** @return T */
	public function get()
	{
		return $this->value;
	}

}

/**
 * @template T
 * @param Box<T> $a
 * @param Box<T> $b
 * @return Box<T>
 */
function both(Box $a, Box $b): Box
{
	return $a;
}

function nominalTypes(): void
{
	$a = new Box(new Cat());
	$b = new Box(new Dog());
	$alias = $a;
	$result = both($a, $b);
	assertType('JointInference\\Box<JointInference\\Cat|JointInference\\Dog>', $a);
	assertType('JointInference\\Box<JointInference\\Cat|JointInference\\Dog>', $alias);
	assertType('JointInference\\Box<JointInference\\Cat|JointInference\\Dog>', $result);
	assertNativeType('JointInference\\Box', $result);
}

/**
 * @template T
 * @param Box<T> $a
 * @param Box<T> $b
 */
function consumeBoth(Box $a, Box $b): void
{
}

function noGenericReturn(): void
{
	$a = new Box(1);
	$b = new Box(2);
	consumeBoth($a, $b);
	assertType('JointInference\\Box<1|2>', $a);
	assertType('JointInference\\Box<1|2>', $b);
}

class Event
{
}

/** @template ID of string */
class WrittenEvent extends Event
{

	/** @param ID $id */
	public function __construct($id)
	{
	}

}

/** @template T of Event */
class EventCollection
{

	/** @param list<T> $events */
	public function __construct(array $events)
	{
	}

	/** @param T $event */
	public function add(Event $event): void
	{
	}

}

/** @template ID of string */
class ContainerEvent
{

	/** @param EventCollection<WrittenEvent<ID>> $events */
	public function __construct(EventCollection $events)
	{
	}

}

function nestedCollection(): void
{
	$container = new ContainerEvent(new EventCollection([new WrittenEvent('one'), new WrittenEvent('two')]));
	assertType("JointInference\\ContainerEvent<'one'|'two'>", $container);
}

/**
 * @template T
 * @param Box<covariant T> $a
 * @param Box<covariant T> $b
 */
function observeBoxes(Box $a, Box $b): void
{
}

function covariantInputsStayIndependent(): void
{
	$a = new Box(1);
	$b = new Box('two');
	observeBoxes($a, $b);
	assertType('JointInference\\Box<1>', $a);
	assertType("JointInference\\Box<'two'>", $b);
}

function unpackedArguments(): void
{
	$c = new Box(3);
	$d = new Box(4);
	both(...[$c, $d]);
	assertType('JointInference\\Box<3|4>', $c);
	assertType('JointInference\\Box<3|4>', $d);
}

class Combiner
{

	/**
	 * @template T
	 * @param Box<T> $a
	 * @param Box<T> $b
	 */
	public function combine(Box $a, Box $b): void
	{
	}

	/**
	 * @template T
	 * @param Box<T> ...$boxes
	 */
	public static function combineAll(Box ...$boxes): void
	{
	}

}

function methodCalls(Combiner $combiner): void
{
	$a = new Box(1);
	$b = new Box(2);
	$c = new Box(3);
	$combiner->combine($a, $b);
	Combiner::combineAll($b, $c);
	assertType('JointInference\\Box<1|2|3>', $a);
	assertType('JointInference\\Box<1|2|3>', $b);
	assertType('JointInference\\Box<1|2|3>', $c);
}

/**
 * @template U of Event
 * @extends EventCollection<U>
 */
class ChildCollection extends EventCollection
{
}

function inheritedConstructor(): void
{
	$container = new ContainerEvent(new ChildCollection([new WrittenEvent('one'), new WrittenEvent('two')]));
	assertType("JointInference\\ContainerEvent<'one'|'two'>", $container);
}
