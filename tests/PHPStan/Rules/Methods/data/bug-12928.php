<?php

namespace Bug12928;

class Foo {
	/**
	 * @param non-empty-string $phptFile
	 * @param non-empty-string $code
	 *
	 * @return non-empty-string
	 */
	public function render(string $phptFile, string $code): string
	{
		return str_replace(
			[
				'__DIR__',
				'__FILE__',
			],
			[
				"'" . dirname($phptFile) . "'",
				"'" . $phptFile . "'",
			],
			$code,
		);
	}
}

class FooBar {
	/**
	 * @param non-empty-string $phptFile
	 * @param non-falsy-string $code
	 * @param array<non-falsy-string> $replace
	 *
	 * @return non-falsy-string
	 */
	public function render(string $code, array $replace): string
	{
		return str_replace(
			[
				'__DIR__',
				'__FILE__',
			],
			$replace,
			$code,
		);
	}
}


class FooBarBaz {
	/**
	 * @param non-empty-string $phptFile
	 * @param non-empty-string $code
	 *
	 * @return non-empty-string
	 */
	public function render(string $code, array $replace): string
	{
		return str_replace(
			[
				'__DIR__',
				'__FILE__',
			],
			$replace,
			$code,
		);
	}
}
