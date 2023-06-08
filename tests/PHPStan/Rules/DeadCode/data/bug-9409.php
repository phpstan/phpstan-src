<?php

namespace Bug9409;

class HelloWorld
{
	/** @var array<string,string|null> $tempDir */
	private static $tempDir = [];

	public function getTempDir(string $name): ?string
	{
		if (isset($this::$tempDir[$name])) {
			return $this::$tempDir[$name];
		}

		$path = '';

		$this::$tempDir[$name] = $path;

		return $path;
	}
}
