<?php

use PHPStan\TrinaryLogic;
use function PHPStan\Analyser\assertVariableCertainty;

function () {
	try {
		$foo = bar();
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
};

function () {
	try {
		$foo = bar();
		throw new \Exception();
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createNo(), $foo);
};

function () {
	try {
		throw new \Exception();
		$foo = bar();
	} finally {
		assertVariableCertainty(TrinaryLogic::createNo(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createNo(), $foo);
};

function () {
	try {
		$foo = bar();
	} catch (Exception $exception) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
};

function () {
	try {
		$foo = bar();
	} catch (Exception $exception) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		$foo = null;
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
};

function () {
	try {
		$foo = bar();
	} catch (Throwable $exception) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
		$foo = null;
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createYes(), $foo);
};

function () {
	try {
		if (random()) {
			$foo = bar();
		}
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
	assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
};
