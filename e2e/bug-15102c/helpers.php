<?php declare(strict_types = 1);

// The global helper whose name collides with the alias - Laravel loads these via
// Composer's autoload.files, so the function exists before analysis starts and
// function_exists('Redirect') is true (function names are case-insensitive).
function redirect(): int
{
	return 1;
}
