<?php

namespace Bug8926;

class Foo {
	private bool $test;

	/** @param int[] $arr */
	function success(array $arr) : void {
		$test = false;
		(function($arr) use(&$test) {
			$test = count($arr) == 1;
		})($arr);

		if ($test) {
			echo "...\n";
		}
	}

	/** @param int[] $arr */
	function error(array $arr) : void {
		$this->test = false;
		(function($arr) {
			$this->test = count($arr) == 1;
		})($arr);


		if ($this->test) {
			echo "...\n";
		}
	}
}
