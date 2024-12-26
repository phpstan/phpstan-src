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
		/** @param positive-int|array<string> $v */
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

/**
 * @template T
 */
class GenericFoo
{

}

class MissingTypes
{

	public array $a {
		set { // do not report, taken care of above the property
		}
	}

	/** @var array<string> */
	public array $b {
		set { // do not report, inherited from property
		}
	}

	public array $c {
		set (array $v) { // do not report, taken care of above the property

		}
	}

	/** @var array<string> */
	public array $d {
		set (array $v) { // do not report, inherited from property

		}
	}

	public int $e {
		/** @param array<string> $v */
		set (int|array $v) { // do not report, type specified

		}
	}

	public int $f {
		set (int|array $v) { // report

		}
	}

	public int $g {
		set (int|GenericFoo $value) { // report

		}
	}

	public int $h {
		set (int|callable $value) { // report

		}
	}

}
