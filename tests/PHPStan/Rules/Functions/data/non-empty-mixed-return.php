<?php

namespace NonEmptyMixedReturn;

/** @return non-empty-mixed */
function returnsEmptyString()
{
	return '';
}

/** @return non-empty-mixed */
function returnsNull()
{
	return null;
}

/** @return non-empty-mixed */
function returnsEmptyArray()
{
	return [];
}

/** @return non-empty-mixed */
function returnsNonEmptyString()
{
	return 'x';
}

/**
 * @param string $string
 * @return non-empty-mixed
 */
function returnsGeneralString($string)
{
	return $string;
}

/**
 * @param mixed $value
 * @return non-empty-mixed
 */
function returnsPlainMixed($value)
{
	return $value;
}
