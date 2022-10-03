<?php

class HelloWorld
{
	public function test(string $url): bool
	{
		$urlParsed = parse_url($url);

		return isset($urlParsed['path']);
	}
}
