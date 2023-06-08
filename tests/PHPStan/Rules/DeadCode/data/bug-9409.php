<?php

namespace Bug9409;

class HelloWorld
{
	/** @var array<string,string|null> $tempDir */
	private static array $tempDir = [];

	public function getTempDir(string $name): string|null
	{
		if (isset($this::$tempDir[$name])) {
			return $this::$tempDir[$name];
		}

		$path = '';

		$this::$tempDir[$name] = $path;

		return $path;
	}
}
