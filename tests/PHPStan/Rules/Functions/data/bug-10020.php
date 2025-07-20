<?php declare(strict_types = 1);

namespace Bug10020;

function load(string $path, string ...$extraPaths): void
{
	//$this->doLoad(false, \func_get_args());
}

$projectDir = __DIR__;
$environment = 'dev';

$files = array_reverse(array_filter([
	$projectDir.'/.env',
	$projectDir.'/.env.'.$environment,
	$projectDir.'/.env.local',
	$projectDir.'/.env.'.$environment.'.local',
], 'file_exists'));

if ($files !== []) {
	load(...$files);
}
