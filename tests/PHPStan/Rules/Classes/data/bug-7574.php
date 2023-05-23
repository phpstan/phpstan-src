<?php

namespace Bug7574;

abstract class C
{
	/**
	 * @return static
	 */
	protected static function s() {
		$c = get_called_class();
		return new $c();
	}
}
