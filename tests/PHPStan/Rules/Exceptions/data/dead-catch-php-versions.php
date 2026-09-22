<?php

namespace DeadCatchPhpVersions;

use ValueError;
use const PHP_VERSION_ID;

class Foo
{

	public function versionCompare(string $a, string $b): void
	{
		if (PHP_VERSION_ID >= 80000) {
			try {
				version_compare($a, $b, 'nope');
			} catch (ValueError $e) {
			}
		}

		if (PHP_VERSION_ID < 80000) {
			try {
				version_compare($a, $b, 'nope');
			} catch (ValueError $e) {
			}
		}
	}

	public function triggerError(string $message): void
	{
		if (PHP_VERSION_ID >= 80000) {
			try {
				trigger_error($message, 12345);
			} catch (ValueError $e) {
			}
		}

		if (PHP_VERSION_ID < 80000) {
			try {
				trigger_error($message, 12345);
			} catch (ValueError $e) {
			}
		}
	}

}
