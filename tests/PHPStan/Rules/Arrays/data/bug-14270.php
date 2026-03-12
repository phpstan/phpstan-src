<?php declare(strict_types = 1);

namespace Bug14270;

class Foo
{
	public function getDuration(string $path): void {
		preg_match('~^([a-z]+)\:\/\/(.+)~', $path, $matches);
		$scheme = null;
		if ($matches !== []) {
			[, $scheme, $path] = $matches;
		}
	}
}
