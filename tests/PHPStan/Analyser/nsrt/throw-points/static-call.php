<?php

namespace ThrowPoints\StaticCall;

use PHPStan\TrinaryLogic;
use ThrowPoints\Helpers\ThrowPointTestObject;
use function PHPStan\Testing\assertVariableCertainty;
use function ThrowPoints\Helpers\doesntThrow;
use function ThrowPoints\Helpers\maybeThrows;

function () {
	try {
		ThrowPointTestObject::staticThrows();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		ThrowPointTestObject::staticMaybeThrows();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		ThrowPointTestObject::staticDoesntThrow();
		$foo = 1;
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		ThrowPointTestObject::{doesntThrow()}($foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		ThrowPointTestObject::{maybeThrows()}($foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};

function () {
	try {
		ThrowPointTestObject::staticDoesntThrow(doesntThrow(), $foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}
};

function () {
	try {
		ThrowPointTestObject::staticDoesntThrow(maybeThrows(), $foo = 1);
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
	}
};
