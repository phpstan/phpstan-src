<?php

declare(strict_types=1);

function configValue(string $key): mixed
{
	return null;
}

function configuredConnectionValue(string $selectorKey): mixed
{
	return null;
}

function tenantConfigValue(string $key): mixed
{
	return null;
}

set_exception_handler(static function (\Throwable $e): void {
	fwrite(STDERR, 'Swallowed by global exception handler: ' . $e->getMessage() . "\n");
	exit(0);
});
