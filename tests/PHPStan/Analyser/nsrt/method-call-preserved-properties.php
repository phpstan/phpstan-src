<?php // lint >= 8.1

declare(strict_types = 1);

namespace MethodCallPreservedProperties;

use function PHPStan\Testing\assertType;

/** Called private method: what it writes is all it writes. */
class PrivateCallee
{

	public bool $foo = false;

	private bool $bar = false;

	protected bool $baz = false;

	public function run(): void
	{
		$this->foo = true;
		$this->bar = true;
		$this->baz = true;
		$this->writeBar();
		assertType('true', $this->foo);
		assertType('false', $this->bar);
		assertType('true', $this->baz);
	}

	private function writeBar(): void
	{
		echo 'x';
		$this->bar = false;
	}

}

/** A property a closure of the class writes can change whenever whoever holds the closure runs it, so the walk's type does not hold after the call. */
class ClosureWritesAfterCall
{

	/** @var list<int> */
	private array $history = [];

	private ?int $plain = null;

	public function test(): void
	{
		$callback = $this->prepare();
		assertType('list<int>', $this->history);
		assertType('1', $this->plain);
		$callback();
	}

	public function overridable(): void
	{
		$this->history = [];
		$this->prepareOverridable();
		assertType('list<int>', $this->history);
	}

	private function prepare(): \Closure
	{
		$this->history = [];
		$this->plain = 1;

		return function (): void {
			$this->history[] = 1;
		};
	}

	public function prepareOverridable(): bool
	{
		$this->history = [];

		return true;
	}

}

/** Called private method calling an overridable one: an override can write public and protected properties, not private ones. */
class PrivateCalleeCallingPublic
{

	public bool $foo = false;

	private bool $bar = false;

	protected bool $baz = false;

	private bool $qux = false;

	public function run(): void
	{
		if (!$this->bar) {
			return;
		}
		$this->foo = true;
		$this->baz = true;
		$this->qux = true;
		$this->viaPrivate();
		assertType('bool', $this->foo);
		assertType('true', $this->bar);
		assertType('bool', $this->baz);
		assertType('true', $this->qux);
	}

	private function viaPrivate(): void
	{
		$this->other();
	}

	public function other(): void
	{
		echo 'x';
	}

	public function setQux(): void
	{
		$this->qux = false;
	}

}

/** Overridable public method: public and protected forgotten, private kept unless a public/protected method writes it. */
class PublicCallee
{

	public bool $foo = false;

	private bool $bar = false;

	protected bool $baz = false;

	private bool $written = false;

	public function run(): void
	{
		$this->doSomething();
		assertType('bool', $this->foo);
		assertType('bool', $this->baz);
		assertType('bool', $this->written);
	}

	public function check(): void
	{
		if (!$this->bar) {
			return;
		}
		$this->doSomething();
		assertType('true', $this->bar);
	}

	public function doSomething(): void
	{
		echo 'x';
	}

	protected function setWritten(): void
	{
		$this->written = true;
	}

}

/** Final class with a private method calling $this->other() that writes only $bar. */
final class FinalClass
{

	public bool $foo = false;

	private bool $bar = false;

	protected bool $baz = false;

	public function run(): void
	{
		$this->foo = true;
		$this->bar = true;
		$this->baz = true;
		$this->viaPrivate();
		assertType('true', $this->foo);
		assertType('false', $this->bar);
		assertType('true', $this->baz);

		$this->doNothing();
		assertType('true', $this->foo);
	}

	private function viaPrivate(): void
	{
		$this->other();
	}

	public function other(): void
	{
		echo 'x';
		$this->bar = false;
	}

	public function doNothing(): void
	{
		echo 'x';
	}

}

/** Final method: the call cannot reach an override. */
class FinalMethod
{

	public bool $foo = false;

	public bool $bar = false;

	public function run(): void
	{
		$this->foo = true;
		$this->bar = true;
		$this->writeBar();
		assertType('true', $this->foo);
		assertType('false', $this->bar);
	}

	final public function writeBar(): void
	{
		echo 'x';
		$this->bar = false;
	}

}

/** Every kind of write counts. */
final class WriteKinds
{

	public int $assign = 0;

	public int $compound = 0;

	public int $inc = 0;

	public ?int $unset = 0;

	/** @var list<int> */
	public array $push = [];

	public int $ref = 0;

	/** @var list<int> */
	public array $foreachRef = [];

	public int $byRefArg = 0;

	public int $closure = 0;

	public int $listed = 0;

	public int $untouched = 0;

	public function run(): void
	{
		$this->assign = 1;
		$this->compound = 1;
		$this->inc = 1;
		$this->unset = 1;
		$this->push = [1];
		$this->ref = 1;
		$this->foreachRef = [1];
		$this->byRefArg = 1;
		$this->closure = 1;
		$this->listed = 1;
		$this->untouched = 1;
		$this->writeAll();
		assertType('2', $this->assign);
		assertType('int', $this->compound);
		assertType('int', $this->inc);
		assertType('int|null', $this->unset);
		assertType('non-empty-list<int>', $this->push);
		assertType('int', $this->ref);
		assertType('list<int>', $this->foreachRef);
		assertType('int', $this->byRefArg);
		assertType('int', $this->closure);
		assertType('4', $this->listed);
		assertType('1', $this->untouched);
	}

	private function writeAll(): void
	{
		$this->assign = 2;
		$this->compound += 2;
		$this->inc++;
		unset($this->unset);
		$this->push[] = 2;
		$r = &$this->ref;
		foreach ($this->foreachRef as &$item) {
			$item = 3;
		}
		self::byRef($this->byRefArg);
		$f = function (): void {
			$this->closure = 3;
		};
		[$this->listed] = [4];
	}

	private static function byRef(int &$x): void
	{
		$x = 5;
	}

}

/** A write through another object of the class may be a write to $this. */
final class Aliasing
{

	public int $value = 0;

	public int $other = 0;

	public function run(self $source): void
	{
		$this->value = 1;
		$this->other = 1;
		$this->copyFrom($source);
		assertType('int', $this->value);
		assertType('1', $this->other);
	}

	private function copyFrom(self $source): void
	{
		echo 'x';
		$source->value = 2;
	}

}

/** Only the class's own code is read: inherited methods, parent:: and inherited properties are outside it. */
class ParentClass
{

	public bool $foo = false;

	public bool $bar = false;

	public function callX(): void
	{
		$this->x();
	}

	public function x(): void
	{
		echo 'x';
	}

}

final class ChildClass extends ParentClass
{

	public function run(): void
	{
		$this->foo = true;
		$this->bar = true;
		$this->callX();
		assertType('bool', $this->foo);
		assertType('bool', $this->bar);

		$this->foo = true;
		$this->bar = true;
		parent::x();
		assertType('bool', $this->foo);
		assertType('bool', $this->bar);
	}

	public function x(): void
	{
		echo 'x';
		$this->bar = false;
	}

}

/** self:: and parent:: calls run exactly the named method. */
class StaticCalls extends ParentClass
{

	public bool $baz = false;

	public function run(): void
	{
		$this->foo = true;
		$this->baz = true;
		self::writeBaz();
		assertType('bool', $this->foo);
		assertType('false', $this->baz);

		$this->foo = true;
		$this->baz = true;
		static::writeBaz();
		assertType('bool', $this->foo);
		assertType('bool', $this->baz);
	}

	public function writeBaz(): void
	{
		echo 'x';
		$this->baz = false;
	}

}

/** Mutual recursion. */
final class Recursion
{

	public bool $foo = false;

	public bool $bar = false;

	public bool $baz = false;

	public function run(): void
	{
		$this->foo = true;
		$this->bar = true;
		$this->baz = true;
		$this->ping(3);
		assertType('bool', $this->foo);
		assertType('bool', $this->bar);
		assertType('true', $this->baz);
	}

	private function ping(int $n): void
	{
		$this->foo = false;
		if ($n > 0) {
			$this->pong($n - 1);
		}
	}

	private function pong(int $n): void
	{
		$this->bar = false;
		if ($n > 0) {
			$this->ping($n - 1);
		}
	}

}

trait WritesBar
{

	private function writeBar(): void
	{
		echo 'x';
		$this->bar = false;
	}

}

/** A trait's methods are not in the using class's code. */
final class UsesTrait
{

	use WritesBar;

	public bool $foo = false;

	public bool $bar = false;

	public function run(): void
	{
		$this->foo = true;
		$this->bar = true;
		$this->writeBar();
		assertType('bool', $this->foo);
		assertType('bool', $this->bar);
	}

}

trait HelpsRoute
{

	protected function helper(): int
	{
		return 1;
	}

}

abstract class TraitRouteParent
{

	public function dispatch(): int
	{
		return $this->post();
	}

	abstract protected function post(): int;

}

/** A trait's code can write any property of the class, so a class using one keeps types across calls like any other class. */
class TraitRoute extends TraitRouteParent
{

	use HelpsRoute;

	private ?\stdClass $order = null;

	public function handle(): void
	{
		$this->reset();
		assertType('stdClass|null', $this->order);
		$this->dispatch();
		assertType('stdClass|null', $this->order);
	}

	private function reset(): void
	{
		$this->order = null;
	}

	protected function post(): int
	{
		$this->order = new \stdClass();

		return 1;
	}

}

/** Receivers other than $this are not followed. */
final class Receiver
{

	public bool $foo = false;

	public bool $bar = false;

	public function writeBar(): void
	{
		echo 'x';
		$this->bar = false;
	}

}

function receiver(Receiver $r): void
{
	$r->foo = true;
	$r->bar = true;
	$r->writeBar();
	assertType('bool', $r->foo);
	assertType('bool', $r->bar);
}

/** Deeper fetches and remembered method results are still forgotten. */
final class Deeper
{

	public ?Receiver $receiver = null;

	public function run(): void
	{
		if ($this->receiver === null || !$this->receiver->foo) {
			return;
		}
		$this->doNothing();
		assertType(Receiver::class, $this->receiver);
		assertType('bool', $this->receiver->foo);
	}

	private function doNothing(): void
	{
		echo 'x';
	}

}

/** Fallbacks: everything is forgotten as before. */
final class Fallbacks
{

	public bool $foo = false;

	public string $name = 'foo';

	public function escapes(): void
	{
		$this->foo = true;
		$this->passThis();
		assertType('bool', $this->foo);
	}

	public function dynamicWrite(): void
	{
		$this->foo = true;
		$this->writeDynamic();
		assertType('bool', $this->foo);
	}

	public function dynamicCall(): void
	{
		$this->foo = true;
		$this->callDynamic();
		assertType('bool', $this->foo);
	}

	public function callable(): void
	{
		$this->foo = true;
		$this->callableThis();
		assertType('bool', $this->foo);
	}

	private function passThis(): void
	{
		helper($this);
	}

	private function writeDynamic(): void
	{
		echo 'x';
		$this->{$this->name} = false;
	}

	private function callDynamic(): void
	{
		$method = $this->name;
		$this->$method();
	}

	private function callableThis(): void
	{
		array_map([$this, 'passThis'], [1]);
	}

}

function helper(Fallbacks $f): void
{
	$f->foo = false;
}

/** Magic __set: an undeclared write runs code the summary cannot see. */
final class MagicSet
{

	public bool $foo = false;

	public function run(): void
	{
		$this->foo = true;
		$this->writeUndeclared();
		assertType('bool', $this->foo);
	}

	private function writeUndeclared(): void
	{
		echo 'x';
	}

	public function __set(string $name, mixed $value): void
	{
		$this->foo = false;
	}

}

/** Interface and abstract methods are overridable. */
abstract class AbstractCallee
{

	public bool $foo = false;

	private bool $bar = false;

	public function run(): void
	{
		if (!$this->bar) {
			return;
		}
		$this->foo = true;
		$this->template();
		assertType('bool', $this->foo);
		assertType('true', $this->bar);
	}

	abstract protected function template(): void;

}

/** A remembered readonly property is initialized, so no call can change it. */
class ReadonlyProperties
{

	public readonly ?int $public;

	protected readonly ?int $protected;

	private readonly ?int $private;

	public function __construct(?int $value)
	{
		$this->public = $value;
		$this->protected = $value;
		$this->private = $value;
	}

	public function run(): void
	{
		if ($this->public === null || $this->protected === null || $this->private === null) {
			return;
		}
		$this->write();
		assertType('int', $this->public);
		assertType('int', $this->protected);
		assertType('int', $this->private);
	}

	public function write(): bool
	{
		$this->public = 1;
		$this->protected = 1;
		$this->private = 1;

		return true;
	}

}

/** A property passed to a method of a class named in a static call or 'new' is written only when the parameter is by reference. */
class ClassMethodArguments
{

	private ?int $staticByValue = null;

	private ?int $staticByRef = null;

	private ?int $newByValue = null;

	private ?int $newByRef = null;

	private ?int $noConstructor = null;

	public function run(): void
	{
		if ($this->staticByValue === null || $this->staticByRef === null || $this->newByValue === null || $this->newByRef === null || $this->noConstructor === null) {
			return;
		}
		$this->pass();
		assertType('int', $this->staticByValue);
		assertType('int|null', $this->staticByRef);
		assertType('int', $this->newByValue);
		assertType('int|null', $this->newByRef);
		assertType('int', $this->noConstructor);
	}

	public function pass(): bool
	{
		ArgumentTaker::byValue($this->staticByValue);
		ArgumentTaker::byRef($this->staticByRef);
		new ArgumentTaker($this->newByValue);
		new ByRefConstructor($this->newByRef);
		new \stdClass($this->noConstructor);

		return true;
	}

}

final class ArgumentTaker
{

	public function __construct(?int $value)
	{
	}

	public static function byValue(?int $value): void
	{
	}

	public static function byRef(?int &$value): void
	{
	}

}

final class ByRefConstructor
{

	public function __construct(?int &$value)
	{
	}

}

/** A property passed to a method of the class is written only when the parameter is by reference. */
class MethodArguments
{

	private ?int $byValue = null;

	private ?int $byRef = null;

	private ?int $named = null;

	public function run(): void
	{
		if ($this->byValue === null || $this->byRef === null || $this->named === null) {
			return;
		}
		$this->check();
		assertType('int', $this->byValue);
		assertType('int|null', $this->byRef);
		assertType('int', $this->named);
	}

	public function check(): bool
	{
		return $this->isValid($this->byValue)
			&& self::isValidStatic(b: $this->named)
			&& $this->takeRef($this->byRef);
	}

	/** @param mixed $value */
	protected function isValid($value): bool
	{
		return $value !== null;
	}

	protected static function isValidStatic(?int &$a = null, ?int $b = null): bool
	{
		return $b !== null;
	}

	private function takeRef(?int &$value): bool
	{
		return $value !== null;
	}

}

/**
 * A call that can run code outside the class - a function, another object's method, 'new' -
 * forgets public and protected properties; private ones when the followed code or the class's closures write them.
 */
final class OutsideCalls
{

	public bool $public = false;

	protected bool $protected = false;

	private bool $private = false;

	private bool $writtenByPublicMethod = false;

	private bool $writtenInClosure = false;

	private bool $writtenByConstructor;

	public function __construct(
		private Receiver $receiver,
		private bool $promoted = false,
	)
	{
		$this->writtenByConstructor = false;
	}

	public function run(): void
	{
		$this->public = true;
		$this->protected = true;
		$this->private = true;
		$this->writtenByPublicMethod = true;
		$this->writtenInClosure = true;
		$this->writtenByConstructor = true;
		$this->promoted = true;
		$this->callsFunction();
		assertType('bool', $this->public);
		assertType('bool', $this->protected);
		assertType('true', $this->private);
		assertType('true', $this->writtenByPublicMethod);
		assertType('bool', $this->writtenInClosure);
		assertType('true', $this->writtenByConstructor);
		assertType('true', $this->promoted);

		$this->private = true;
		$this->callsOtherObject();
		assertType('true', $this->private);
		assertType(Receiver::class, $this->receiver);

		$this->private = true;
		$this->instantiates();
		assertType('true', $this->private);
	}

	private function callsFunction(): void
	{
		file_put_contents('log file', 'foo');
	}

	private function callsOtherObject(): void
	{
		$this->receiver->writeBar();
	}

	private function instantiates(): void
	{
		new Receiver();
	}

	public function setWrittenByPublicMethod(): void
	{
		$this->writtenByPublicMethod = false;
	}

	private function createClosure(): \Closure
	{
		return function (): void {
			$this->writtenInClosure = false;
		};
	}

}

/** A by-value function argument is not a write, a by-reference one is. */
final class FunctionArguments
{

	/** @var list<int> */
	private array $counted = [1];

	/** @var list<int> */
	private array $sorted = [1];

	public function run(): void
	{
		$this->counted = [2];
		$this->sorted = [2];
		$this->callFunctions();
		assertType('array{2}', $this->counted);
		assertType('list<int>', $this->sorted);
	}

	private function callFunctions(): void
	{
		echo count($this->counted);
		sort($this->sorted);
	}

}

function noParameters(): void
{
}

function lastParameterByRef(int $a, ?int &$b): void
{
}

function variadicByRef(int $a, ?int &...$rest): void
{
}

/**
 * An argument past the last parameter is by value, unless the function is variadic
 * and its variadic parameter is by reference; a function that does not exist can write it.
 */
final class FunctionArgumentPositions
{

	private int $extraForNoParameters = 1;

	private int $extraForLastByRef = 1;

	private int $extraForVariadicByRef = 1;

	private int $unknownFunction = 1;

	public function run(): void
	{
		$this->extraForNoParameters = 2;
		$this->extraForLastByRef = 2;
		$this->extraForVariadicByRef = 2;
		$this->unknownFunction = 2;
		$this->callFunctions();
		assertType('2', $this->extraForNoParameters);
		assertType('2', $this->extraForLastByRef);
		assertType('int', $this->extraForVariadicByRef);
		assertType('int', $this->unknownFunction);
	}

	private function callFunctions(): void
	{
		$x = null;
		noParameters($this->extraForNoParameters);
		lastParameterByRef(1, $x, $this->extraForLastByRef);
		variadicByRef(1, $x, $this->extraForVariadicByRef);
		doesNotExist($this->unknownFunction);
	}

}

/** A call not known to have side effects forgets a property it passes to a by-reference parameter. */
final class MaybeImpureFunctionArguments
{

	/** @var list<int> */
	private array $counted = [1];

	/** @var list<int> */
	private array $sorted = [1];

	public function run(): void
	{
		$this->counted = [2];
		$this->sorted = [2];
		$this->countAndSort();
		assertType('array{2}', $this->counted);
		assertType('list<int>', $this->sorted);
	}

	private function countAndSort(): bool
	{
		sort($this->sorted);

		return count($this->counted) > 0;
	}

}

/** What the class hands out - first-class callables and closures - runs when outside code runs. */
final class HandedOutCallables
{

	/** @var list<string> */
	private array $linkList = [];

	/** @var list<string> */
	private array $closureList = [];

	private bool $untouched = false;

	private ?\Closure $stored = null;

	public function firstClassCallable(string $text): void
	{
		$this->linkList = [];
		$this->untouched = true;
		$this->converter($text);
		assertType('list<string>', $this->linkList);
		assertType('true', $this->untouched);
	}

	public function storedClosure(): void
	{
		$this->closureList = [];
		$this->runStored();
		assertType('list<string>', $this->closureList);
	}

	private function converter(string $text): void
	{
		preg_replace_callback('/x/', $this->pregCallback(...), $text);
	}

	/** @param array<int|string, string> $matches */
	private function pregCallback(array $matches): string
	{
		$this->linkList[] = $matches[0];
		return '';
	}

	public function store(): void
	{
		$this->stored = function (): void {
			$this->closureList[] = 'x';
		};
	}

	private function runStored(): void
	{
		if ($this->stored !== null) {
			($this->stored)();
		}
	}

}

/** An array callable lets $this out, so code outside can call any method whenever it runs. */
final class ArrayCallable
{

	/** @var list<string> */
	private array $linkList = [];

	private bool $untouched = false;

	public function arrayCallable(string $text): void
	{
		$this->linkList = [];
		$this->untouched = true;
		$this->arrayConverter($text);
		assertType('list<string>', $this->linkList);
		assertType('bool', $this->untouched);
	}

	private function arrayConverter(string $text): void
	{
		preg_replace_callback('/x/', [$this, 'pregCallback'], $text);
	}

	/** @param array<int|string, string> $matches */
	private function pregCallback(array $matches): string
	{
		$this->linkList[] = $matches[0];
		return '';
	}

}

/** Once $this gets out, code outside can call close() during any call that runs it. */
final class ThisLetOut
{

	private bool $open = true;

	public function close(): void
	{
		$this->open = false;
	}

	public function run(\Closure $onEmpty): void
	{
		$this->open = true;
		$onEmpty($this);
		$this->callOutside();
		assertType('bool', $this->open);
	}

	public function withoutOutsideCode(): void
	{
		$this->open = true;
		$this->nothingOutside();
		assertType('true', $this->open);
	}

	private function callOutside(): void
	{
		echo strlen('x');
	}

	private function nothingOutside(): void
	{
		echo 'x';
	}

}

/** A call not known to have side effects forgets only the properties it writes. */
final class MaybeImpureCall
{

	private string $currentLine = '';

	private int $currentLineNb = -1;

	/** @var list<string> */
	private array $lines = [];

	public function parse(string $value): void
	{
		$this->currentLineNb = -1;
		$this->currentLine = '';
		$this->lines = explode("\n", $value);
		$this->moveToNextLine();
		assertType('string', $this->currentLine);
		assertType('non-empty-list<string>', $this->lines);
	}

	private function moveToNextLine(): bool
	{
		if ($this->currentLineNb >= count($this->lines) - 1) {
			return false;
		}

		$this->currentLine = $this->lines[++$this->currentLineNb];

		return true;
	}

}

function anonymousClass(): void
{
	new class {

		private bool $foo = false;

		private bool $bar = false;

		public function run(): void
		{
			$this->foo = true;
			$this->bar = true;
			$this->writeBar();
			assertType('true', $this->foo);
			assertType('false', $this->bar);
		}

		private function writeBar(): void
		{
			echo 'x';
			$this->bar = false;
		}

	};
}

/** The constructor's callees are walked before it, so their walks narrow and initialize what they assign. */
class ConstructorCallee
{

	private int $foo;

	public int $bar;

	public function __construct()
	{
		$this->init();
		assertType('1', $this->foo);
		$this->initBar();
		assertType('int', $this->bar);
	}

	private function init(): void
	{
		$this->foo = 1;
	}

	public function initBar(): void
	{
		$this->bar = 2;
	}

}

/** A possibly impure call that writes a property on some paths forgets it: the walk ends with no type for it. */
class SometimesWrittenBySelfCall
{

	private int $foo = 0;

	public function run(): void
	{
		$this->foo = 1;
		self::maybeWrite();
		assertType('int', $this->foo);

		$this->foo = 1;
		$this->maybeWrite();
		assertType('int', $this->foo);
	}

	private function maybeWrite(): int
	{
		if (rand(0, 1) === 1) {
			$this->foo = 2;
		}

		return 1;
	}

}

abstract class OverriddenWritesParent
{

	public function dispatch(): int
	{
		echo 'x';

		return $this->post();
	}

	abstract protected function post(): int;

}

/**
 * The parent's code can run an override of its method whenever it runs, which assigns a private property too: after a
 * call that runs a walked method, the property has the type the walk ends with or a type the override assigns. A property
 * only methods the parent does not have write takes the walk's type.
 */
class OverriddenWrites extends OverriddenWritesParent
{

	private ?\stdClass $order = null;

	private ?\stdClass $other = null;

	public function handle(): void
	{
		$this->reset();
		assertType('stdClass|null', $this->order);
		assertType('null', $this->other);
		$this->dispatch();
		assertType('stdClass|null', $this->order);
		assertType('null', $this->other);
	}

	private function reset(): void
	{
		$this->order = null;
		$this->other = null;
	}

	protected function post(): int
	{
		$this->order = new \stdClass();

		return 1;
	}

	public function setOther(\stdClass $other): void
	{
		$this->other = $other;
	}

}

abstract class OverriddenCreatesParent
{

	abstract public function check(): void;

	public function getId(): int
	{
		echo 'x';

		return 1;
	}

}

/** An override the parent's code can run assigns only a narrower type than declared, so the walk's type of what it assigns stays narrowed. */
class OverriddenCreates extends OverriddenCreatesParent
{

	private ?\stdClass $client = null;

	public function check(): void
	{
		$this->create();
	}

	private function create(): void
	{
		$this->client ??= new \stdClass();
	}

	public function update(): void
	{
		$this->create();
		assertType('stdClass', $this->client);
		$this->getId();
		assertType('stdClass', $this->client);
	}

}

/** An override the parent's code can run writes the property in a way no walk sees the value of, so the walk's type does not hold after the call. */
class OverriddenWritesByReference extends OverriddenCreatesParent
{

	private ?\stdClass $client = null;

	public function check(): void
	{
		\settype($this->client, 'null');
	}

	private function create(): void
	{
		$this->client ??= new \stdClass();
	}

	public function update(): void
	{
		$this->create();
		assertType('stdClass|null', $this->client);
	}

}

abstract class ArgumentOverridesParent
{

	abstract public function in(): int;

	abstract public function out(): int;

}

final class DefinitionResolver
{

	public function resolve(?\stdClass $definition): void
	{
		echo 'x';
	}

}

/** An override that only passes a property to a method of another object does not keep the walk's type of what the class assigns from holding. */
class ArgumentOverrides extends ArgumentOverridesParent
{

	private ?\stdClass $definition = null;

	public function __construct(private DefinitionResolver $resolver)
	{
	}

	public function in(): int
	{
		$this->load();
		assertType('stdClass', $this->definition);

		return 1;
	}

	public function out(): int
	{
		$this->load();
		$this->resolver->resolve($this->definition);

		return 1;
	}

	private function load(): void
	{
		$this->definition ??= new \stdClass();
	}

}

/** An overridable callee: a private property only the class's own code writes has its old type or the type the walk of the class's method ends with. */
class OverridableCalleeWrites
{

	private ?\stdClass $client = null;

	public ?\stdClass $publicClient = null;

	private ?int $sometimes = null;

	public function run(): void
	{
		if ($this->client === null || $this->publicClient === null) {
			return;
		}
		$this->sometimes = 1;
		$this->connect();
		assertType('stdClass', $this->client);
		assertType('stdClass|null', $this->publicClient);
		assertType('int|null', $this->sometimes); // the walk starts from the declared type
	}

	public function connect(): bool
	{
		$this->client ??= new \stdClass();
		$this->publicClient ??= new \stdClass();
		if (rand(0, 1) === 1) {
			$this->sometimes = 2;
		}

		return true;
	}

}


class EscapedThisSender
{

	public function __construct(EscapedThis $server)
	{
	}

}

/** $this passed to another class's code: that code can call any method of the class, so only private properties those methods write are forgotten. */
class EscapedThis
{

	private ?\stdClass $console = null;

	private ?\stdClass $client = null;

	private ?EscapedThisSender $sender = null;

	public function __construct(bool $b)
	{
		if ($b) {
			$this->console = new \stdClass();
		}
	}

	public function tick(): void
	{
		if ($this->console === null || $this->client === null) {
			return;
		}
		$this->sender ??= new EscapedThisSender($this);
		$this->dispatch();
		assertType('stdClass', $this->console);
		assertType('stdClass|null', $this->client);
	}

	public function dispatch(): void
	{
		echo 'x';
	}

	public function disconnect(): void
	{
		$this->client = null;
	}

}

/** $this kept as a singleton and passed to a class named at runtime lets it out the same way. */
class EscapedThisSingleton
{

	private static ?self $instance = null;

	private ?\stdClass $console = null;

	private ?\stdClass $client = null;

	public function __construct(bool $b)
	{
		self::$instance = $this;
		if ($b) {
			$this->console = new \stdClass();
		}
	}

	/** @param class-string<EscapedThisSender> $class */
	public function tick(string $class): void
	{
		if ($this->console === null || $this->client === null) {
			return;
		}
		new $class($this);
		$this->dispatch();
		assertType('stdClass', $this->console);
		assertType('stdClass|null', $this->client);
	}

	public function dispatch(): void
	{
		echo 'x';
	}

	public function disconnect(): void
	{
		$this->client = null;
	}

}

/** A copy of $this in a variable can have its private properties written through it, so nothing is kept. */
class EscapedThisAlias
{

	private ?\stdClass $console = null;

	public function tick(): void
	{
		if ($this->console === null) {
			return;
		}
		$this->dispatch();
		assertType('stdClass|null', $this->console);
	}

	public function dispatch(): void
	{
		$self = $this;
		echo 'x';
	}

}
