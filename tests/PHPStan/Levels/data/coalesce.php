<?php

function (): void {
	echo $foo ?? 'foo';

	echo $bar->bar ?? 'foo';
};

function (\ReflectionClass $ref): void {
	echo $ref->name ?? 'foo';
	echo $ref->nonexistent ?? 'bar';
};

function (?string $s): void {
	echo $a ?? 'foo';

	echo $s ?? 'bar';

	if ($s !== null) {
		return;
	}

	echo $s ?? 'bar';
};
