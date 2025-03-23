<?php declare(strict_types = 1);

namespace Bug8719;

class HelloWorld
{
	private const CASE_1 = 1;
	private const CASE_2 = 2;
	private const CASE_3 = 3;

	/**
	 * @return self::CASE_*
	 */
	private function getCase(): int
	{
		return random_int(1,3);
	}

	public function ok(): string
	{
		switch($this->getCase()) {
			case self::CASE_1:
				$foo = 'bar';
				break;
			case self::CASE_2:
				$foo = 'baz';
				break;
			case self::CASE_3:
				$foo = 'barbaz';
				break;
		}

		return $foo;
	}

	public function not_ok(): string
	{
		switch($this->getCase()) {
			case self::CASE_1:
				$foo = 'bar';
				break;
			case self::CASE_2:
			case self::CASE_3:
				$foo = 'barbaz';
				break;
		}

		return $foo;
	}
}
