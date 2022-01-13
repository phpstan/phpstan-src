<?php

namespace Bug3867;

class Demo
{
	public function analyze(): void
	{
		$try = [
			"methodOne",
			"methodTwo"
		];

		do {
			$method = array_shift($try);

			$result = $this->$method();

			if (!empty($result)) {
				break;
			}
		} while (count($try) > 0);
	}
}
