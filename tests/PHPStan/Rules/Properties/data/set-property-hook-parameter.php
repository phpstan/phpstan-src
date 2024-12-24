<?php

namespace SetPropertyHookParameter;

class Foo
{

	public int $ok {
		set (int $v) {

		}
	}

	/** @var positive-int */
	public int $ok2 {
		set (int $v) {

		}
	}

	/** @var positive-int */
	public int $ok3 {
		/** @param positive-int|array<string> */
		set (int|array $v) {

		}
	}

	public $ok4 {
		set ($v) {

		}
	}

}

class Bar
{

	public $a {
		set (int $v) {

		}
	}

	public int $b {
		set ($v) {

		}
	}

	public int $c {
		set (string $v) {

		}
	}

	public int|string $d {
		set (string $v) {

		}
	}

	public int $e {
		/** @param positive-int $v */
		set (int $v) {

		}
	}

	public int $f {
		/** @param positive-int|array<string> $v */
		set (int|array $v) {

		}
	}

}
