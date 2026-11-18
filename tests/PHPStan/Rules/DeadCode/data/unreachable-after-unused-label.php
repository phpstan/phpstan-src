<?php

namespace UnreachableAfterUnusedLabel;

function afterTerminatingGoto(int $value): int
{
	if ($value > 1) {
		goto end;
		dead:
		$items = [$value, 1];
		foreach ($items as $item) {
			$value += $item;
		}

		end:
	}

	return $value;
	unreachable:
	$value = $value * 1;
	return $value;
}

function unusedLabelInReachableCode(int $value): int
{
	unused:
	$value++;
	return $value;
}

function usedLabel(int $value): int
{
	if ($value > 1) {
		goto end;
	}
	return 1;
	end:
	return $value;
}

function reachedOnlyByBackwardGoto(int $value): int
{
	goto second;
	first:
	$value++;
	return $value;
	second:
	goto first;
}

function reachedByJumpIntoBlock(int $value): int
{
	if ($value > 1) {
		return 1;
		inside:
		$value++;
	}
	if ($value === 0) {
		goto inside;
	}
	return $value;
}

function severalUnusedLabels(): void
{
	return;
	one:
	echo 1;
	echo 2;
	two:

	three:
	echo 3;
}

goto end;
lbl:
$a = array(1);
end:
