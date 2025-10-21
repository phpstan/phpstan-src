<?php

declare(strict_types = 1);

namespace Bug13708;

class HelloWorld
{
	/**
	 * @param non-empty-string $s
	 */
	public function takeNonEmpty(string $s): void
	{
		return;
	}

	public function doStuff(): void
	{
		$this->takeNonEmpty(
			strtr('change {me}', ['{me}' => 'me'])
		);
	}
}
