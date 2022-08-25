<?php

namespace Bug7563;

declare(strict_types=1);

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public static function sayHello(string $dimension): void
	{
		$result = sscanf($dimension, '%[1234567890.]%s');
		assertType('array{string|null, string|null}|null', $result);
	}

	public static function sayFoo() {
		$error = 'TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException [1423347324] at /var/www/html/app/web/typo3/sysext/core/Classes/Resource/ResourceStorage.php:2496: Folder "1203-0110-0008" already exists.';
		$exceptionComponents = sscanf($error, '%s [%d] at %[^:]:%d: %[^[]]');

		if (null === $exceptionComponents) {
			throw new Exception($error);
		}

		assertType('array{string|null, int|null, string|null, int|null, string|null}', $exceptionComponents);
	}

	// see https://3v4l.org/Y5T2R
	public static function edgeCase(string $dimension): void
	{
		$result = sscanf($dimension, '%[%[]');
		assertType('array{string|null}|null', $result);
	}

}
