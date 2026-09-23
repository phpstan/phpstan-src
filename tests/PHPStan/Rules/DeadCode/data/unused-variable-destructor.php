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

class ChildCache extends Cache
{
}

function scopeGuard(): void
{
	$guard = new Cache();
	echo 'work';
}

function inheritedDestructor(): void
{
	$guard = new ChildCache();
	echo 'work';
}

function releasedEarly(): void
{
	$guard = new Cache();
	echo 'work';
	$guard = null;
	echo 'more work';
}

function maybeDestructible(bool $b): void
{
	$guard = $b ? new Cache() : null;
	echo 'work';
}

function noDestructor(): void
{
	$object = new \stdClass();
	echo 'work';
}
