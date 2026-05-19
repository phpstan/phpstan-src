<?php // lint >= 8.4

declare(strict_types = 1);

namespace GotoPropertyHook;

use function PHPStan\Testing\assertType;

class BackwardGotoInHook
{

	public int $value {
		get {
			$i = 0;
			retry:
			$i++;
			$val = rand(0, 1) ? 42 : null;
			if ($val === null) {
				goto retry;
			}
			assertType('int<1, max>', $i);
			return $val;
		}
	}

}

class ForwardGotoInHook
{

	public int $value {
		get {
			$a = rand(0, 1) ? 42 : false;
			if ($a === false) {
				goto fallback;
			}

			assertType('42', $a);
			return $a;

			fallback:
			assertType('false', $a);
			return 0;
		}
	}

}

class ForwardGotoFallThroughInHook
{

	public int $value {
		get {
			$a = rand(0, 1) ? 42 : false;
			if ($a === false) {
				goto fallback;
			}

			assertType('42', $a);

			fallback:
			assertType('42|false', $a);
			return $a !== false ? $a : 0;
		}
	}

}
