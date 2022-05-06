<?php

namespace Bug5295;

class Demo
{
	public function analyze(): void
	{
		foreach ($this->getValue() as $key => $val) {
			if ($key >= 5 && $key <= 10) {
			} elseif ($key > 10 && $key <= 15) {
			} else {
			}
		}
	}

	/**
	 * @return array
	 */
	public function getValue(): array {
		return [];
	}
}
