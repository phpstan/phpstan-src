<?php declare(strict_types = 1);

namespace OffsetAccessAssignmentPhpIntMax;

function (): void {
	$a = [
		9223372036854775807 => 4,
	];
	$a[] = 5;
};

function (): void {
	$a = [];
	$a[9223372036854775807] = 4;
	$a[] = 5;
};

function (): void {
	$a = [
		9223372036854775807 => 4,
	];
	$a[10] = 5;
};
