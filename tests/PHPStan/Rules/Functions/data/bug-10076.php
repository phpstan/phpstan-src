<?php

namespace Bug10076;

/**
 * @template T
 *
 * @param 'object'|'array'|class-string<T> $type
 *
 * @return list<mixed>
 */
function result($type = 'object'): array
{
	return [];
}

function () {
	result();
	result('object');
	result('array');
	result(\DateTime::class);
};
