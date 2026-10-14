<?php declare(strict_types = 1);

namespace Bug15329;

use Exception;

class C {
	function __serialize() {
		return ['a' => 'b'];
	}
	/** @param array<string, string> $a */
	function __unserialize(array $a) {
		if (rand(0,1) == 0)  {
			throw new Exception("nope");
		}
		return;
	}
}

$c = new C();
$s = serialize($c);

try {
	unserialize($s);
} catch (Exception $e) {
	echo "caught " . $e->getMessage();
}
