<?php declare(strict_types = 1);

namespace Bug8776_1;

use LogicException;

/**
 * @param mixed $value
 *
 * @return array|bool
 *
 * @throws LogicException
 */
function validate($value, array $schema = null)
{
	if (is_int($value)) {
		if (isset($schema['minimum'])) {
			$minimum = $schema['minimum'];
			if (filter_var($minimum, FILTER_VALIDATE_INT) === false) {
				throw new LogicException('`minimum` must be `int`');
			}
			$options = ['options' => ['min_range' => $minimum]];
			$filtered = filter_var($value, FILTER_VALIDATE_INT, $options);
			if ($filtered === false) {
				return compact('minimum', 'value');
			}
		}
		if (isset($schema['maximum'])) {
			$maximum = $schema['maximum'];
			if (filter_var($maximum, FILTER_VALIDATE_INT) === false) {
				throw new LogicException('`maximum` must be `int`');
			}
			$options = ['options' => ['max_range' => $maximum]];
			/** @var int|false */
			$filtered = filter_var($value, FILTER_VALIDATE_INT, $options);
			if ($filtered === false) {
				return compact('maximum', 'value');
			}
		}
		// ...
	}
	if (is_string($value)) {
		// ...
	}
	return true;
}
