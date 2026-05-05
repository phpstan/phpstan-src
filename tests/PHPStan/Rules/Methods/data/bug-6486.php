<?php

namespace Bug6486;

class HelloWorld
{
	/** @var ?string */
	private $only = null;
	/** @var ?non-empty-string */
	private $exclude = null;

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	private function isAllowed($name)
	{
		if (!$this->only && !$this->exclude) {
			return true;
		}

		if ($this->only) {
			return Preg::isMatch($this->only, $name);
		}

		return !Preg::isMatch($this->exclude, $name);
	}
}

class Preg {
	/**
	 * @param non-empty-string   $pattern
	 * @param string   $subject
	 * @param array<string|null> $matches Set by method
	 * @param int      $flags PREG_UNMATCHED_AS_NULL, only available on PHP 7.2+
	 * @param int      $offset
	 * @return bool
	 */
	public static function isMatch($pattern, $subject, &$matches = null, $flags = 0, $offset = 0)
	{
		return true;
	}
}
