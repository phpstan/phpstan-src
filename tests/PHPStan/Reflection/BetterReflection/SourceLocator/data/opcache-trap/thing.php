<?php declare(strict_types = 1);

namespace OpcacheTrap;

// one function per file, named so that the PSR-4 prefix resolves OpcacheTrap\thing
// to this very path - the php-standard-library / azjezz/psl layout
function thing(): string
{
	return 'x';
}
