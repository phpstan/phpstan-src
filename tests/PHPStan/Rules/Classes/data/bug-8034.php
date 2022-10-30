<?php declare(strict_types = 1);

namespace Bug8034;

abstract class HelloWorld
{
	/**
	 * @return array
	 */
	private static function getFields() : array
	{
		if (isset(static::$fields)) {
			return static::$fields;
		} elseif (defined('static::FIELDS')) {
			$aOutput = explode(',', static::FIELDS);

			return $aOutput;
		} else {
			return [];
		}
	}
}
