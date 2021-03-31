<?php

namespace OverwrittenExitPoint;

function (): string {
	try {
		if (rand(0, 1)) {
			throw new \Exception();
		}

		return 'foo';
	} catch (\Exception $e) {
		return 'bar';
	} finally {
		return 'baz';
	}
};

function (): string {
	try {

	} finally {
		return 'foo';
	}
};

function (): string {
	try {
		return 'foo';
	} finally {

	}
};
