<?php

namespace DependentVariableCertainty;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function (bool $b, bool $c): void {
	if ($b) {
		$foo = 'bla';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}

	if ($b) {

	}

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}

	if ($b) {
		$d = true;
	}

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}

	if ($c) {
		$bar = 'ble';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	assertVariableCertainty(TrinaryLogic::createMaybe(), $bar);

	if ($c) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertVariableCertainty(TrinaryLogic::createYes(), $bar);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertVariableCertainty(TrinaryLogic::createNo(), $bar);
	}
};

function (bool $b): void {
	if ($b) {
		$foo = 'bla';
	}

	$b = true;

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function (bool $b): void {
	if ($b) {
		$foo = 'bla';
	}

	$foo = 'ble';

	assertVariableCertainty(TrinaryLogic::createYes(), $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function (bool $a, bool $b) {
	if ($a) {
		$lorem = 'test';
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	while ($b) {
		$foo = 'foo';
		if (rand(0, 1)) {
			break;
		}
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	if ($b) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}
};

function (bool $a, bool $b) {
	if ($a) {
		$lorem = 'test';
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	$i = 0;
	while ($b) {
		$foo = 'foo';
		$i++;
		if (rand(0, 1)) {
			break;
		}
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	if ($b) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo); // could be Yes
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}
};

function (bool $a, bool $b): void
{
	if ($a) {
		$lorem = 'test';
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	unset($b);

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}
};

function (bool $a, bool $b): void
{
	if ($a) {
		$lorem = 'test';
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	unset($lorem);

	assertVariableCertainty(TrinaryLogic::createNo(), $lorem);

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createNo(), $lorem);
	}
};

function (bool $is_valid_a): void {
	if ($is_valid_a) {
		$a = new \stdClass();
	} else {
		$a = null;
	}

	assertType('stdClass|null', $a);

	if ($is_valid_a) {
		assertType('stdClass', $a);
	} else {
		assertType('null', $a);
	}
};

function (?\stdClass $a): void {
	if ($a) {
		$is_valid_a = true;
	} else {
		$is_valid_a = false;
	}

	assertType('stdClass|null', $a);

	if ($is_valid_a) {
		assertType('stdClass', $a);
	} else {
		assertType('null', $a);
	}
};


function (bool $a, bool $b): void
{
	if ($a) {
		$lorem = 'test';
	}

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
	}

	unset($a);

	assertVariableCertainty(TrinaryLogic::createMaybe(), $lorem);

	if ($a) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $lorem);
	}
};

function (): void {
	$from = null;
	$to = null;
	if (rand(0, 1)) {
		$from = new \stdClass();
		$to = new \stdClass();
	}

	if ($from !== null) {
		assertType('stdClass', $to);
	}
};

/*function (): void {
	$from = null;
	$to = null;
	if (rand(0, 1)) {
		$from = new \stdClass();
		$to = new \stdClass();
	}

	if (rand(0, 1)) {
		$from = new \stdClass();
		$to = new \stdClass();
	}

	if ($from !== null) {
		assertType('stdClass', $to);
	}
};

function (bool $b): void {
	$from = null;
	$to = null;
	if ($b) {
		$from = new \stdClass();
		$to = new \stdClass();
	}

	if ($from !== null) {
		assertType('true', $b);
		assertType('stdClass', $from);
		assertType('stdClass', $to);
	}
};

function (bool $b): void {
	$from = null;
	$to = null;
	if ($b) {
		$from = new \stdClass();
		$to = new \stdClass();
	}

	if ($b) {
		assertType('true', $b);
		assertType('stdClass', $from);
		assertType('stdClass', $to);
	}
};

function (bool $b, bool $c): void {
	if ($b) {
		if ($c) {
			$foo = 'bla';
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		if ($c) {
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		if (!$c) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	}

	if (!$b && !$c) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
};

function (bool $b, bool $c): void {
	if ($b && $c) {
		$foo = 'bla';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		if ($c) {
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		if (!$c) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	}

	if (!$b && !$c) {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
};

function (bool $b, bool $c, bool $d): void {
	if ($b) {
		if ($c) {
			$foo = 'bla';
		}
	}

	if ($d) {
		$foo = 'ble';
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	assertType('\'bla\'|\'ble\'', $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('\'bla\'|\'ble\'', $foo);
		if ($c) {
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
			assertType('\'bla\'|\'ble\'', $foo);
			if (!$d) {
				assertVariableCertainty(TrinaryLogic::createYes(), $foo);
				assertType('\'bla\'', $foo);
			} else {
				assertVariableCertainty(TrinaryLogic::createYes(), $foo);
				assertType('\'ble\'', $foo);
			}
		}
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('\'ble\'', $foo);
		if (!$c) {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
			assertType('\'ble\'', $foo);
			if (!$d) {
				assertVariableCertainty(TrinaryLogic::createNo(), $foo);
				assertType('*ERROR*', $foo);
			}
		}
	}

	if (!$b && !$c) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('\'ble\'', $foo);
		if (!$d) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
			assertType('*ERROR*', $foo);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
			assertType('\'ble\'', $foo);
		}
	}

	if ($d) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
		assertType('\'bla\'|\'ble\'', $foo);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('\'bla\'', $foo);
		if (!$b && !$c) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
			assertType('*ERROR*', $foo);
		}
	}
};

function (bool $b, bool $c, bool $d): void {
	if ($d) {
		$foo = 'ble';
	}

	if ($b) {
		if ($c) {
			$foo = 'bla';
		}
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	assertType('\'bla\'|\'ble\'', $foo);

	if ($b) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('\'bla\'|\'ble\'', $foo);
		if ($c) {
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
			assertType('\'bla\'', $foo);
			if (!$d) {
				assertVariableCertainty(TrinaryLogic::createYes(), $foo);
				assertType('\'bla\'', $foo);
			} else {
				assertVariableCertainty(TrinaryLogic::createYes(), $foo);
				assertType('\'bla\'', $foo);
			}
		} else {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
			assertType('\'ble\'', $foo);
			if ($d) {
				assertVariableCertainty(TrinaryLogic::createYes(), $foo);
				assertType('\'ble\'', $foo);
			} else {
				assertVariableCertainty(TrinaryLogic::createNo(), $foo);
			}
		}
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		assertType('\'ble\'', $foo);
		if ($d) {
			assertVariableCertainty(TrinaryLogic::createYes(), $foo);
			assertType('\'ble\'', $foo);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}
	}
};*/
