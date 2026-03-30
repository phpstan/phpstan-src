<?php declare(strict_types = 1);

namespace Bug10903;

class HelloWorld
{
	public bool $hasFooKey = false;

	public function sayHello(): void
	{
		/** @var array<string, string> $arr */
		$arr = ['foo' => 'bar'];
		$this->hasFooKey = false;

		$filteredArr = \array_filter($arr, function ($value, $key) {
            if (\stripos($key, 'foo') !== false) {
				$this->hasFooKey = true;

                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

		if ($this->hasFooKey) {
            // do something
        }
	}
}
