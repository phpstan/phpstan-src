<?php

namespace Bug4558;

use DateTime;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var DateTime[]
	 */
	private $suggestions = [];

	public function sayHello(): ?DateTime
	{
		while (count($this->suggestions) > 0) {
			assertType('non-empty-array<DateTime>', $this->suggestions);
			assertType('int<1, max>', count($this->suggestions));
			$try = array_shift($this->suggestions);

			assertType('array<DateTime>', $this->suggestions);
			assertType('int<0, max>', count($this->suggestions));

			if (rand(0, 1)) {
				return $try;
			}

			assertType('array<DateTime>', $this->suggestions);
			assertType('int<0, max>', count($this->suggestions));

			// we might be out of suggested days, so load some more
			if (count($this->suggestions) === 0) {
				assertType('array{}', $this->suggestions);
				assertType('0', count($this->suggestions));
				$this->createSuggestions();
			}

			assertType('array<DateTime>', $this->suggestions);
			assertType('int<0, max>', count($this->suggestions));
		}

		return null;
	}

	private function createSuggestions(): void
	{
		$this->suggestions[] = new DateTime;
	}
}
