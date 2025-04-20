<?php

namespace Bug4864;

class Example
{
	/** @var mixed */
	private $value;
	private bool $isHandled;

	public function fetchValue(callable $f): void
	{
		$this->isHandled = false;
		$this->value = null;

		(function () {
			$this->isHandled = true;
			$this->value = 'value';
		})();

		if ($this->isHandled) {
			$f($this->value);
		}
	}
}
