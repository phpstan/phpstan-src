<?php

class HelloWorld
{
	public function test(string $url): bool
	{
		$urlParsed = parse_url($url);

		return isset($urlParsed['path']);
	}

	public function test2(closure $closure): bool
	{
		return isset($closure['path']);
	}

	/**
	 * @param iterable<int|string, object> $iterable
	 */
	public function test3($iterable): bool
	{
		unset($iterable['path']);
	}
}
