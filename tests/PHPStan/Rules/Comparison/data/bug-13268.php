<?php declare(strict_types = 1);

namespace Bug13268;

class HelloWorld
{
	public function assignInCall(string $string): ?string
	{
		if (
			is_string(
				$string = realpath($string)
			)
		) {
			throw new \RuntimeException('Refusing to overwrite existing file: ' . $string);
		}
		return null;
	}

	public function dumpToLog(mixed $dumpToLog): ?string
	{
		if (is_string($dumpToLog)) {
			if (file_exists($dumpToLog) || is_string($dumpToLog = realpath($dumpToLog))) {
				throw new \RuntimeException('Refusing to overwrite existing file: ' . $dumpToLog);
			}
		}
		return null;
	}
}
