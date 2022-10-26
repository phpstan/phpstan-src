<?php declare(strict_types = 1);

namespace Bug4739;

class HelloWorld
{
	public function check(): bool
	{
		$counter = [];

		return (static function () use (&$counter): bool {
			if (! isset($counter['key'])) {
				$counter['key'] = 0;
			}

			return ++$counter['key'] === 2;
		})();
	}
}
