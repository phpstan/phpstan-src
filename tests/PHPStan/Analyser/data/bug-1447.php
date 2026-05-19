<?php

namespace Bug1447;

class Tst {
	/** @param mixed[] $abc */
	function _initArgs($abc): void
	{
		foreach ($abc as $k => $v) {
			if ($v === 'a') $e = true;
			else if ($v === 'b') $e = true;
			else if ($v === 'c') $e = true;
			else if ($v === 'd') $e = true;
			else if ($v === 'e') $e = true;
			else if ($v === 'f') $e = true;
			else if ($v === 'g') $e = true;
			else if ($v === 'h') $e = true;
			else if ($v === 'i') $e = true;
			else if ($v === 'j') $e = true;
			else if ($v === 'k') $e = true;
			else if ($v === 'l') $e = true;
			else if ($v === 'm') $e = true;
			else if ($v === 'n') $e = true;
			else if ($v === 'o') $e = true;
		}
	}
}
