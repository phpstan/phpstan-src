<?php declare(strict_types = 1);

namespace Bug6486;

use function PHPStan\Testing\assertType;

class Preg {
	/**
	 * @param non-empty-string   $pattern
	 * @param string   $subject
	 * @return bool
	 */
	public static function isMatch($pattern, $subject)
	{
		return true;
	}
}

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

		assertType('non-falsy-string', $this->exclude);
		return !Preg::isMatch($this->exclude, $name);
	}
}
