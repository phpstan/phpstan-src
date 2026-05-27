<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug10128;

enum FooBar {
	case Bar;
	case Baz;
	case Foo;
}

function getFoo(): FooBar {
	return FooBar::Bar;
}

function test(): void {
	$first = getFoo();
	$second = getFoo();

	$type = match ([$first, $second]) {
		[FooBar::Bar, FooBar::Bar] => 1,
		[FooBar::Bar, FooBar::Baz] => 2,
		[FooBar::Bar, FooBar::Foo] => 3,

		[FooBar::Baz, FooBar::Bar] => 1,
		[FooBar::Baz, FooBar::Baz] => 2,
		[FooBar::Baz, FooBar::Foo] => 3,

		[FooBar::Foo, FooBar::Bar] => 1,
		[FooBar::Foo, FooBar::Baz] => 2,
		[FooBar::Foo, FooBar::Foo] => 3,
	};

	$type2 = match ([$first, $second]) {
		[FooBar::Bar, FooBar::Bar],
		[FooBar::Bar, FooBar::Baz],
		[FooBar::Bar, FooBar::Foo] => 1,

		[FooBar::Baz, FooBar::Bar] => 1,
		[FooBar::Baz, FooBar::Baz] => 2,
		[FooBar::Baz, FooBar::Foo] => 3,

		[FooBar::Foo, FooBar::Bar] => 1,
		[FooBar::Foo, FooBar::Baz] => 2,
		[FooBar::Foo, FooBar::Foo] => 3,
	};

	$type3 = match ([$first, $second]) {
		[FooBar::Bar, FooBar::Bar],
		[FooBar::Baz, FooBar::Baz],
		[FooBar::Foo, FooBar::Foo],
		[FooBar::Foo, FooBar::Baz] => 1,

		[FooBar::Bar, FooBar::Baz],
		[FooBar::Baz, FooBar::Bar],
		[FooBar::Bar, FooBar::Foo],
		[FooBar::Foo, FooBar::Bar] => 2,

		[FooBar::Baz, FooBar::Foo] => 3,
	};
}

enum TwoCases {
	case Foo;
	case Bar;
}

function getTwoCases(): TwoCases {
	return TwoCases::Foo;
}

function testTwoCases(): void {
	$first = getTwoCases();
	$second = getTwoCases();

	$type = match ([$first, $second]) {
		[TwoCases::Foo, TwoCases::Foo] => 1,
		[TwoCases::Bar, TwoCases::Bar] => 1,

		[TwoCases::Bar, TwoCases::Foo] => 2,
		[TwoCases::Foo, TwoCases::Bar] => 2,
	};
}
