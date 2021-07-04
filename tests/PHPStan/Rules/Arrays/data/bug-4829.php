<?php

namespace Bug4829;

class Test
{
	// PHPStan 0.12.83 level 7 error:
	// 26	Offset 'cat3' does not exist on array('cat1' => 'blah1', 'cat2' => 'blah2', ?'cat3' => 'blah3').
	public function stan() : void
	{
		$ret = [];

		// if $result has 1 element, the error does not occur
		// if $result is [], the error occurs
		$result = ["val1", "val2"];

		foreach ($result as $val) {
			// if I replace $val with a string, the error does not occur
			//$val = "test";

			// if I remove one assignment, the error does not occur
			$ret[$val]['cat1'] = "blah1";
			$ret[$val]['cat2'] = "blah2";
			$ret[$val]['cat3'] = 'blah3';

			$t1 = $ret[$val]['cat1'];
			$t2 = $ret[$val]['cat2'];
			$t3 = $ret[$val]['cat3']; // error occurs here
		}
	}
}
