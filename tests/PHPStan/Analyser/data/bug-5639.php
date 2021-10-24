<?php

namespace Bug5639;

if (\version_compare(\PHP_VERSION, '7.0', '<'))
{
	class Foo extends \Exception {
		function __toString(): string {
			$result = \sprintf("%s\n\nin %s on line %s", $this->message, $this->file, $this->line);
			return $result;
		}
	}
}
else
{
	class Foo extends \Error {
		function __toString(): string {
			$result = \sprintf("%s\n\nin %s on line %s", $this->message, $this->file, $this->line);
			return $result;
		}
	}
}
