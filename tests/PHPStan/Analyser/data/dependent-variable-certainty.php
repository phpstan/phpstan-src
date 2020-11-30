<?php

namespace DependentVariableCertainty;

use PHPStan\TrinaryLogic;
use function PHPStan\Analyser\assertType;
use function PHPStan\Analyser\assertVariableCertainty;

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
