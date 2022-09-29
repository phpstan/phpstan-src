<?php

namespace Bug8072;

function say(\Closure $bar): string
{
	return $bar();
}

function (): void {
	echo say(fn (string $name = null) => 'Hi');
	echo say((fn (string $name = null) => 'Hi')(...));

	echo say(function (string $name = null) {
		return 'Hi';
	});
	echo say((function (string $name = null) {
		return 'Hi';
	})(...));
};
