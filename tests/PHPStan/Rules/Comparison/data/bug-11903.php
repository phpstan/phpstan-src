<?php declare(strict_types = 1);

namespace Bug11903;

class HelloWorld
{
	public function test(bool $first, bool $second): string
	{
		if (! $first && $second) {
            return 'false true';
        }

        if (! $first && ! $second) {
			return 'false false';
        }

        if ($first && $second) {
            return 'true true';
        }

		if ($first && ! $second) {
			return 'true false';
        }

		return 'noop';
	}
}
