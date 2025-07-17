<?php

namespace NoCommentsAfterAttributes;

enum Foo
{
	/** This is a doc comment before attributes. */
	#[Good]
	case Foo;

	#[Bad]
	/** This is a doc comment after attributes. */
	case Baz;
}
