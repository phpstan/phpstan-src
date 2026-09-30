<?php

namespace ResultCacheE2EValueDependency;

function service(string $id): object
{
	return new \stdClass();
}

/**
 * @return string|int
 */
function parameter(string $name)
{
	return $name === '' ? 0 : '';
}
