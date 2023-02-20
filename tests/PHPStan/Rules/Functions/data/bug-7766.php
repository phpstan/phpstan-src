<?php

namespace Bug7766;

/**
 * @return array<array{
 *   id: int,
 *   created: \DateTimeInterface,
 *   updated: \DateTimeInterface,
 *   valid_from: \DateTimeInterface,
 *   valid_till: \DateTimeInterface,
 *   string: string,
 *   other_string: string,
 *   another_string: string,
 *   count: int<0, max>,
 *   other_count: int<0, max>
 * }>
 */
function problem(): array {
	return [[
		'id' => 1,
		'created' => new \DateTimeImmutable(),
		'updated' => new \DateTimeImmutable(),
		'valid_from' => new \DateTimeImmutable(),
		'valid_till' => new \DateTimeImmutable(),
		'string' => 'string',
		'other_string' => 'string',
		'another_string' => 'string',
		'count' => '4',
		'other_count' => 3,
	]];
}
