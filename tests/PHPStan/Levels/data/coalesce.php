<?php

function (): void {
	echo $foo ?? 'foo';

	echo $bar->bar ?? 'foo';
};

function (\ReflectionClass $ref): void {
	echo $ref->name ?? 'foo';
	echo $ref->nonexistent ?? 'bar';
};
