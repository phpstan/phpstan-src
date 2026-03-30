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

	/** @param int[] $arr */
	function errorArrayFilter(array $arr) : void {
		$this->test = false;
		$prices = array_filter($arr, function($elt) {
			if ($elt === 1) {
				$this->test = true;
			}

			return $elt === 2;
		});


		if ($this->test) {
			echo "...\n";
		}
	}

	/** @param int[] $arr */
	function successLocal(array $arr) : void {
		$test = false;
		$prices = array_filter($arr, function($elt) use(&$test) {
			if ($elt === 1) {
				$test = true;
			}

			return $elt === 2;
		});


		if ($test) {
			echo "...\n";
		}
	}
}
