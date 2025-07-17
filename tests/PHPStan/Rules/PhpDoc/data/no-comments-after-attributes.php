<?php

namespace NoCommentsAfterAttributes;

/** This is a doc comment. */
#[Good]
class Good
{
	/** @var class-string*/
	#[Good]
	public const FOO = 'Foo';

	/** @var array<int, string> */
	#[Good]
	private array $foo = [];

	public function __construct(
		/** @var array<int, string> */
		#[Good]
		private array $bar,
		/** @var array<int, string> */
		#[Good]
		array $baz,
		#[Good] string $qux,
	) {}

	// This is a comment.
	#[Good]
	public function foo(): void {}

	/** This is a doc comment. */
	#[Good]
	public function bar(): void {}
}

#[Bad]
/** This is a doc comment. */
class Bad
{
	#[Bad]
	/** @var class-string */
	public const BAR = 'Bar';

	#[Bad]
	/** @var array<int, string> */
	private array $foo = [];

	public function __construct(
		#[Bad]
		/** @var array<int, string> */
		private array $bar,
		#[Bad]
		/** @var array<int, string> */
		array $baz,
	) {}

	#[Bad]
	// This is a comment after attributes.
	public function foo(): void {}

	#[Bad]
	/** This is a doc comment after attributes. */
	public function bar(): void {}
}

/** This is a doc comment. */
#[Good]
function foo(): void {}

#[Bad]
/** This is a doc comment. */
function bar(): void {}
