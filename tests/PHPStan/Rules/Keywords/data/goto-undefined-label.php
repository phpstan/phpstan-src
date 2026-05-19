<?php declare(strict_types = 1);

namespace GotoUndefinedLabel;

function validGoto(): void
{
	goto end;
	echo "unreachable";
	end:
	echo "done";
}

function undefinedLabel(): void
{
	goto nonexistent;
}

function crossBoundaryClosure(): void
{
	outside:
	$fn = function () {
		goto outside;
	};
}

function crossBoundaryAnonymousClass(): void
{
	outside:
	$obj = new class {
		public function doSomething(): void
		{
			goto outside;
		}
	};
}

function crossBoundaryNestedFunction(): void
{
	outside:
	function inner(): void
	{
		goto outside;
	}
}

function labelInClosureGotoOutside(): void
{
	$fn = function () {
		inside:
		echo "hello";
	};
	goto inside;
}

function validBackwardGoto(): void
{
	retry:
	$result = rand(0, 1);
	if ($result === 0) {
		goto retry;
	}
}

function validNestedGoto(): void
{
	try {
		retry:
		$result = rand(0, 1);
		if ($result === 0) {
			throw new \Exception();
		}
	} catch (\Exception $e) {
		goto retry;
	}
}

function validGotoInIf(): void
{
	if (rand(0, 1) === 1) {
		goto end;
	}
	echo "hello";
	end:
	echo "done";
}
