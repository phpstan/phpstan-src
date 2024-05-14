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
