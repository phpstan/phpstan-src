<?php declare(strict_types = 1);

namespace UnusedLabel;

function usedLabel(): void
{
	goto end;
	echo "unreachable";
	end:
	echo "done";
}

function unusedLabel(): void
{
	unused:
	echo "hello";
}

function backwardGotoUsed(): void
{
	retry:
	$result = rand(0, 1);
	if ($result === 0) {
		goto retry;
	}
}

function multipleLabels(): void
{
	goto used;
	unused1:
	echo "not used";
	used:
	echo "used";
	unused2:
	echo "not used either";
}

function crossBoundaryNotUsed(): void
{
	outside:
	$fn = function () {
		goto inside;
		inside:
		echo "hello";
	};
	echo "done";
}

function labelInNestedStructure(): void
{
	if (rand(0, 1) === 1) {
		goto end;
	}
	echo "hello";
	end:
	echo "done";
}
