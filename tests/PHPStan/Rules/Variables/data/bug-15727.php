<?php declare(strict_types = 1);

namespace Bug15727Rule;

function isMulti(): bool
{
	if (defined('MULTI')) {
		return (bool) MULTI;
	}

	return false;
}

function can(string $cap): bool
{
	return $cap === 'update';
}

function variableAssignedUnderOr(): void
{
	if (isMulti()) {
		echo 'network';
	}

	if (!isMulti() || can('update')) {
		$updateData = 1;
	}

	if (!isMulti()) {
		echo $updateData;
	}
}
