<?php

declare(strict_types=1);

namespace Bug15442Rule;

class GridLayout
{
    protected function buildTemplate(): void
    {
		$fx = static function () use (&$fx) {
			(static function () use ($fx) {
				$fx();

				dummy();
			})();
		};

		dummy($fx);
    }

    protected function arrowFunction(): void
    {
		$fx = static function () use (&$fx) {
			(static fn () => $fx())();
		};

		dummy($fx);
    }
}
