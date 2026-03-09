<?php declare(strict_types = 1);

namespace Bug6670;

class HelloWorld
{
	public function sayHello(): string
	{
		try {
			return 'string';
		} finally {
			try {
				$this->clearCache();
			} catch(\Exception $e) {
				return 'same string';
			}
		}
	}

	private function clearCache(): void
	{
		// do...
	}
}
