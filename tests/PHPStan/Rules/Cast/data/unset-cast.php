<?php // lint < 8.0

namespace UnsetCast;

function ($a): void {
	$null = (unset) $a;
};
