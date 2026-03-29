<?php declare(strict_types = 1);

namespace Bug4846;

class Foo
{
	public string $alwaysString = '';

	public ?string $nullableString = null;
}

function (Foo $foo): void {
	echo $foo->alwaysString ?? 'string';

	echo $foo->nullableString ?? 'string';
};
