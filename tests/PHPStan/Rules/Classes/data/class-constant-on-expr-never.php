<?php declare(strict_types = 1); // lint >= 8.0

namespace ClassConstantOnExprNever;

class HelloWorld
{
	public function formatCallable(mixed $callable): string
	{
		if (\is_array($callable)) {
			if (\is_object($callable[0])) {
				return \sprintf('%s::%s()', $callable[0]::class, $callable[1]);
			}

			if (is_string($callable[0])) {
				return \sprintf('%s::%s()', $callable[0], $callable[1]);
			}
		}

		return '';
	}
}
