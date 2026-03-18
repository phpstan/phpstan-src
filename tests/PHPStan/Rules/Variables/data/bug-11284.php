<?php declare(strict_types = 1);

namespace Bug11284;

class HelloWorld
{

	/**
	 * @param array<string> $err
	 * @throws \RuntimeException
	 */
	public function maybeThrows(array &$err): void
	{
		$err[] = 'error';
		if (random_int(0, 1) === 1) {
			throw new \RuntimeException();
		}
	}

	public function test(): void
	{
		$err = [];
		try {
			$this->maybeThrows($err);
		} catch (\RuntimeException $e) {
			if (!empty($err)) {
				echo implode(', ', $err);
			}
		}
	}

}
