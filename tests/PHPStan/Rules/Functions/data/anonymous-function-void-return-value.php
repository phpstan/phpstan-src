<?php

namespace Derp;

function runner(callable $callback): void
{
	$callback();
}

function run(): void
{
	runner(fn() => noop());
}

function noop(): void
{
	return;
}
