<?php

namespace UnresolvableParameter;

class TestDoxPrinter
{
	public function prefixLines(string $prefix, string $message): string
	{
		$message = trim($message);

		return implode(
			PHP_EOL,
			array_map(
				static function (string $text) use ($prefix)
				{
					return '   ' . $prefix . ($text ? ' ' . $text : '');
				},
				preg_split('/\r\n|\r|\n/', $message)
			)
		);
	}
}

interface Collection
{
	/**
	 * @param string-class $class
	 * @return mixed
	 */
	public function pipeInto($class);
}

class User
{
}

function (Collection $collection) {
	$collection->pipeInto(User::class);
};
