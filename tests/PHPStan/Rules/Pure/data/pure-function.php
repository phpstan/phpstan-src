<?php

namespace PureFunction;

/**
 * @phpstan-pure
 */
function doFoo(&$p)
{
	echo 'test';
}

/**
 * @phpstan-pure
 */
function doFoo2(): void
{
	exit;
}

/**
 * @phpstan-pure
 */
function doFoo3(object $obj)
{
	$obj->foo = 'test';
}

/**
 * @phpstan-pure
 */
function pureFunction()
{

}

/**
 * @phpstan-impure
 */
function impureFunction()
{
	echo '';
}

function voidFunction(): void
{
	echo 'test';
}

function possiblyImpureFunction()
{

}

/**
 * @phpstan-pure
 */
function testThese(string $s, callable $cb)
{
	$s();
	$cb();
	pureFunction();
	impureFunction();
	voidFunction();
	possiblyImpureFunction();
	unknownFunction();
}

/**
 * @phpstan-impure
 */
function actuallyPure()
{

}

function voidFunctionThatThrows(): void
{
	if (rand(0, 1)) {
		throw new \Exception();
	}
}

function emptyVoidFunction(): void
{
	$a = 1 + 1;
}

/**
 * @phpstan-assert !null $a
 */
function emptyVoidFunctionWithAssertTag(?int $a): void
{

}

/**
 * @phpstan-pure
 */
function pureButAccessSuperGlobal(): int
{
	$a = $_POST['bla'];
	$_POST['test'] = 1;

	return $_POST['test'];
}

function emptyVoidFunctionWithByRefParameter(&$a): void
{

}

/**
 * @phpstan-pure
 */
function functionWithGlobal(): int
{
	global $db;

	return 1;
}

/**
 * @phpstan-pure
 */
function functionWithStaticVariable(): int
{
	static $v = 1;

	return $v;
}

/**
 * @phpstan-pure
 * @param \Closure(): int $closure2
 */
function callsClosures(\Closure $closure1, \Closure $closure2): int
{
	$closure1();
	return $closure2();
}

/**
 * @phpstan-pure
 * @param pure-callable $cb
 * @param pure-Closure $closure
 * @return int
 */
function callsPureCallableIdentifierTypeNode(callable $cb, \Closure $closure): int
{
	$cb();
	$closure();
}


/** @phpstan-pure */
function justContainsInlineHtml()
{
	?>
	</td>
	</tr>
	</table></td>
	</tr>
	</table>
	<?php
}

/** @phpstan-pure */
function bug13288(array $a)
{
	array_push($a, function () { // error because by ref arg
		exit(); // ok, as array_push() will not invoke the function
	});

	array_push($a, // error because by ref arg
		fn() => exit() // ok, as array_push() will not invoke the function
	);

	$exitingClosure = function () {
		exit();
	};
	array_push($a, // error because by ref arg
		$exitingClosure // ok, as array_push() will not invoke the function
	);

	takesString("exit"); // ok, as the maybe callable type string is not typed with immediately-invoked-callable
}

/** @phpstan-pure */
function takesString(string $s) {
}

/** @phpstan-pure */
function bug13288b()
{
	$exitingClosure = function () {
		exit();
	};

	takesMixed($exitingClosure); // error because immediately invoked
}

/**
 * @phpstan-pure
 * @param-immediately-invoked-callable $m
 */
function takesMixed(mixed $m) {
}

/** @phpstan-pure */
function bug13288c()
{
	$exitingClosure = function () {
		exit();
	};

	takesMaybeCallable($exitingClosure);
}

/** @phpstan-pure */
function takesMaybeCallable(?callable $c) { // arguments passed to functions are considered "immediately called" by default
}

/** @phpstan-pure */
function bug13288d()
{
	$exitingClosure = function () {
		exit();
	};
	takesMaybeCallable2($exitingClosure);
}

/** @phpstan-pure */
function takesMaybeCallable2(?\Closure $c) { // Closures are considered "immediately called"
}

/** @phpstan-pure */
function bug13288e(MyClass $m)
{
	$exitingClosure = function () {
		exit();
	};
	$m->takesMaybeCallable($exitingClosure);
}

class MyClass {
	/** @phpstan-pure */
	function takesMaybeCallable(?callable $c) { // arguments passed to methods are considered "later called" by default
	}
}

