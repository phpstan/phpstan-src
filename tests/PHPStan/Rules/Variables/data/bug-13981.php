<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13981;

$path = match (true) {
	is_dir($baseDir = dirname(__DIR__).'/lang') => $baseDir,
	default => '/translations',
};
