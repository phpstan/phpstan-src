<?php // lint >= 7.4

namespace Bug5952;

class Foo
{
	public function __toString(): string
	{
		throw new \Exception();
	}
}

$foo = new Foo();

try {
	echo $foo;
} catch (\Exception $e) {
	echo "Should be printed";
}

class Bar
{
	/** @throws \Exception */
	public function __toString(): string
	{
		throw new \Exception();
	}
}

$bar = new Bar();

try {
	echo $bar;
} catch (\Exception $e) {
	echo "Should be printed";
}

class Baz
{
	/** @throws void */
	public function __toString(): string
	{
		return 'hello';
	}
}

$baz = new Baz();

try {
	echo $baz;
} catch (\Exception $e) {
	echo "Should not be printed";
}

try {
	echo 123;
} catch (\Exception $e) {
	echo "Should not be printed";
}

/** @var int|Foo $intOrFoo */
$intOrFoo = doFoo();

try {
	echo $intOrFoo;
} catch (\Exception $e) {
	echo "Should be printed";
}

/** @var int|Bar $intOrBar */
$intOrBar = doFoo();

try {
	echo $intOrBar;
} catch (\Exception $e) {
	echo "Should be printed";
}

/** @var int|Baz $intOrBaz */
$intOrBaz = doFoo();

try {
	echo $intOrBaz;
} catch (\Exception $e) {
	echo "Should not be printed";
}

// print statement
try {
	print $foo;
} catch (\Exception $e) {
	echo "Should be printed";
}

try {
	print $baz;
} catch (\Exception $e) {
	echo "Should not be printed";
}

// String concatenation
try {
	$x = 'hello' . $foo;
} catch (\Exception $e) {
	echo "Should be printed";
}

try {
	$x = 'hello' . $baz;
} catch (\Exception $e) {
	echo "Should not be printed";
}

// Concat assignment
try {
	$x = 'hello';
	$x .= $foo;
} catch (\Exception $e) {
	echo "Should be printed";
}

try {
	$x = 'hello';
	$x .= $baz;
} catch (\Exception $e) {
	echo "Should not be printed";
}

// String interpolation
try {
	$x = "hello $foo";
} catch (\Exception $e) {
	echo "Should be printed";
}

try {
	$x = "hello $baz";
} catch (\Exception $e) {
	echo "Should not be printed";
}

// String interpolation with curly braces
try {
	$x = "hello {$foo}";
} catch (\Exception $e) {
	echo "Should be printed";
}

try {
	$x = "hello {$baz}";
} catch (\Exception $e) {
	echo "Should not be printed";
}
