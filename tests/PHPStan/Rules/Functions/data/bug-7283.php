<?php

namespace Bug7283;

/**
 * @template T
 * @param T $value
 * @return array<T>
 */
function onlyTrue(mixed $value): array
{
	return array_fill(0, 5, $value);
}

/**
 * @param array<true> $values
 */
function needTrue(array $values): void {}

function (): void {
	needTrue(onlyTrue(true));
};
