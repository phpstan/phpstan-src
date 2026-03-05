<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13981;

function foo(): string
{
	$path = match (true) {
		is_dir($baseDir = dirname(__DIR__).'/lang') => $baseDir,
		default => '/translations',
	};

	return $path;
}

function foo2(): string
{
	if (rand(0, 1)) {
		$baseDir = '';
	}

	$path = match (true) {
		is_dir($baseDir = dirname(__DIR__).'/lang') => $baseDir,
		default => '/translations',
	};

	return $path;
}

function foo3(): string
{
	$path = match (true) {
		is_dir(dirname(__DIR__).'/lang2') => $baseDir,
		is_dir($baseDir = dirname(__DIR__).'/lang') => $baseDir,
		default => '/translations',
	};

	return $path;
}

function foo4(): string
{
	$path = match (true) {
		is_dir(dirname(__DIR__).'/lang2'),
		is_dir($baseDir = dirname(__DIR__).'/lang') => $baseDir,
		default => '/translations',
	};

	return $path;
}

function foo5(): string
{
	$path = match (true) {
		is_dir($baseDir = dirname(__DIR__).'/lang') => '$baseDir',
		default => $baseDir,
	};

	return $path;
}
