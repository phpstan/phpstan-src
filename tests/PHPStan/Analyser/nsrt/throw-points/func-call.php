<?php

namespace ThrowPoints\FuncCall;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;
use function ThrowPoints\Helpers\maybeThrows;
use function ThrowPoints\Helpers\throws;

function () {
	try {
		throws();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		maybeThrows();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		doesntThrow();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		doesntThrow()($foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		maybeThrows()($foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		doesntThrow(doesntThrow(), $foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		doesntThrow(maybeThrows(), $foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
