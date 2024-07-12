<?php

function (
	$mixed,
	array $array,
	string $string,
	int $int
) {
	$array += [];

	$array + $array;
	$array - $array;

	5 / 2;
	5 / 0;
	5 % (1 - 1);
	$int / 0.0;

	$mixed + [];
	1 + $string;
	1 + "blabla";
	1 + "123";
};

function (
	array $array
) {
	$array += "foo";
};

function (
	array $array
) {
	$array -= $array;
};

function (
	int $int1,
	int $int2,
	string $str1,
	string $str2
) {
	$int1 << $int2;
	$int1 >> $int2;
	$int1 >>= $int2;

	$str1 << $str2;
	$str1 >> $str2;
	$str1 >>= $str2;
};

function (
	int $int1,
	int $int2,
	string $str1,
	string $str2
) {
	$int1 <<= $int2;
	$str1 <<= $str2;
};

function (
	int $int,
	string $string
) {
	$int & 5;
	$int & "5";
	$string & "x";
	$string & 5;
	$int | 5;
	$int | "5";
	$string | "x";
	$string | 5;
	$int ^ 5;
	$int ^ "5";
	$string ^ "x";
	$string ^ 5;
};

function (
	string $string1,
	string $string2,
	stdClass $std,
	\Test\ClassWithToString $classWithToString
) {
	$string1 . $string2;
	$string1 . $std;
	$string1 . $classWithToString;

	$string1 .= $string2;
	$string1 .= $std;
	$string2 .= $classWithToString;
};

function ()
{
	$result = [
		'id' => 'blabla', // string
		'allowedRoomCounter' => 0,
		'roomCounter' => 0,
	];

	foreach ([1, 2] as $x) {
		$result['allowedRoomCounter'] += $x;
	}
};

function () {
	$o = new stdClass;
	$o->user ?? '';
	$o->user->name ?? '';

	nonexistentFunction() ?? '';
};

function () {
	$possibleZero = 0;
	if (doFoo()) {
		$possibleZero = 1;
	}

	5 / $possibleZero;
};

function ($a, array $b, string $c) {
	echo str_replace('abc', 'def', $a) . 'xyz';
	echo str_replace('abc', 'def', $b) . 'xyz';
	echo str_replace('abc', 'def', $c) . 'xyz';

	$strOrArray = 'str';
	if (rand(0, 1) === 0) {
		$strOrArray = [];
	}
	echo str_replace('abc', 'def', $strOrArray) . 'xyz';

	echo str_replace('abc', 'def', $a) + 1;
};

function (array $a) {
	$b = [];
	if (rand(0, 1)) {
		$b['foo'] = 'bar';
	}
	$b += $a;
};

function (array $a) {
	$b = [];
	if (rand(0, 1)) {
		$b['foo'] = 'bar';
	}
	$a + $b;
};

function (stdClass $ob, int $n) {
    $ob == $n;
    $ob + $n;
};

function (array $args) {
	if (isset($args['class'])) {
	}

	[] + $args;
};

function (array $args) {
	if (isset($args['class'])) {
		[] + $args;
	}
};

function (array $args) {
	isset($args['y']) ? $args + [] : $args;
};

/**
 * @param non-empty-string $foo
 * @param string $bar
 * @param class-string $foobar
 * @param literal-string $literalString
 */
function bug6624_should_error($foo, $bar, $foobar, $literalString) {
	echo ($foo + 10);
	echo ($foo - 10);
	echo ($foo * 10);
	echo ($foo / 10);

	echo (10 + $foo);
	echo (10 - $foo);
	echo (10 * $foo);
	echo (10 / $foo);

	echo ($bar + 10);
	echo ($bar - 10);
	echo ($bar * 10);
	echo ($bar / 10);

	echo (10 + $bar);
	echo (10 - $bar);
	echo (10 * $bar);
	echo (10 / $bar);

	echo ($foobar + 10);
	echo ($foobar - 10);
	echo ($foobar * 10);
	echo ($foobar / 10);

	echo (10 + $foobar);
	echo (10 - $foobar);
	echo (10 * $foobar);
	echo (10 / $foobar);

	echo ($literalString + 10);
	echo ($literalString - 10);
	echo ($literalString * 10);
	echo ($literalString / 10);

	echo (10 + $literalString);
	echo (10 - $literalString);
	echo (10 * $literalString);
	echo (10 / $literalString);
}

/**
 * @param numeric-string $numericString
 */
function bug6624_no_error($numericString) {
	echo ($numericString + 10);
	echo ($numericString - 10);
	echo ($numericString * 10);
	echo ($numericString / 10);

	echo (10 + $numericString);
	echo (10 - $numericString);
	echo (10 * $numericString);
	echo (10 / $numericString);

	$numericLiteral = "123";

	echo ($numericLiteral + 10);
	echo ($numericLiteral - 10);
	echo ($numericLiteral * 10);
	echo ($numericLiteral / 10);

	echo (10 + $numericLiteral);
	echo (10 - $numericLiteral);
	echo (10 * $numericLiteral);
	echo (10 / $numericLiteral);
}

function benevolentPlus(array $a, int $i): void {
	foreach ($a as $k => $v) {
		echo $k + $i;
	}
};

function (int $int) {
	$int + [];
};

function testMod(array $a, object $o): void {
	echo 4 % 3;
	echo '4' % 3;
	echo 4 % '3';

	echo $a % 3;
	echo 3 % $a;

	echo $o % 3;
	echo 3 % $o;
}
