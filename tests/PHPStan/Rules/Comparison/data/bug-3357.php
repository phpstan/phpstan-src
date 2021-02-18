<?php

namespace Bug3357;

function (): void {
	/**
	 * @var array{foo?: string}
	 */
	$foo = [];

	assert($foo === []);
};

function (): void {
	/**
	 * @var array{foo: string, bar?: string}
	 */
	$foo = ['foo' => ''];

	assert($foo === ['foo' => '']);
};
