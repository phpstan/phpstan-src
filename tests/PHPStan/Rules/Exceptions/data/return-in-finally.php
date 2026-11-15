<?php

namespace ReturnInFinally;

function withValue()
{
	try {
	} finally {
		return 1;
	}
}

function withoutValue()
{
	try {
	} finally {
		return;
	}
}

function inTryAndCatch()
{
	try {
		return 1;
	} catch (\Exception $e) {
		return 2;
	} finally {
	}
}

function nestedClosureAndClass()
{
	try {
	} finally {
		$f = function () {
			return 1;
		};
		$g = fn () => 2;
		$o = new class () {
			public function foo()
			{
				return 3;
			}
		};
	}
}

function nestedTry()
{
	try {
	} finally {
		try {
			return 1;
		} catch (\Exception $e) {
			return 2;
		} finally {
			return 3;
		}
	}
}

function closureWithTry()
{
	try {
	} finally {
		$f = function () {
			try {
				return 1;
			} finally {
				return 2;
			}
		};
	}
}

function nestedControlFlow(array $a)
{
	try {
	} finally {
		foreach ($a as $v) {
			if ($v) {
				return 1;
			} elseif ($v === 0) {
				switch ($v) {
					case 0:
						return 2;
				}
			} else {
				{
					return 3;
				}
			}
		}
	}
}

function phpVersionBranches()
{
	if (PHP_VERSION_ID >= 80600) {
		try {
		} finally {
			return 1;
		}
	}

	if (PHP_VERSION_ID < 80600) {
		try {
		} finally {
			return 2;
		}
	}
}
