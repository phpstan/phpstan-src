<?php declare(strict_types = 1);

namespace UnusedVariableByRefReturn;

/** @param mixed $v */
function sink($v): void
{
}

function &writeInFinallyAfterByRefReturn(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x = 2;
	}
}

function writeInFinallyAfterByValueReturn(): int
{
	$x = 0;
	try {
		return $x;
	} finally {
		$x = 2;
	}
}

function &otherVariablesStayTracked(): int
{
	$unused = 1;
	$x = 0;

	return $x;
}

class Foo
{

	public function &method(): int
	{
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 2;
		}
	}

}

function closures(): void
{
	$byRef = function &(): int {
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 2;
		}
	};
	$byValue = function (): int {
		$x = 0;
		try {
			return $x;
		} finally {
			$x = 2;
		}
	};
	sink($byRef);
	sink($byValue);
}
