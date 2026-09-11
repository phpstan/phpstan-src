<?php

namespace UnusedVariableDestructor;

class Cache
{
	public function __destruct()
	{
		echo 'flush';
	}
}

function flush(): void
{
	$cache = new Cache();
	unset($cache);
}

function flushOffset(): void
{
	$caches = [new Cache()];
	unset($caches[0]);
}

function flushArray(): void
{
	$caches = [[new Cache()]];
	unset($caches);
}

function discardScalar(): void
{
	$unused = 1;
	unset($unused);
}

function closeStream(string $path): void
{
	$stream = fopen($path, 'r');
	unset($stream);
}
