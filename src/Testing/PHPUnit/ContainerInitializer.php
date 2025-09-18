<?php declare(strict_types = 1);

namespace PHPStan\Testing\PHPUnit;

use PHPStan\DependencyInjection\InvalidIgnoredErrorExceptionTest;
use function array_key_exists;

final class ContainerInitializer
{
	public static function initialize(string $testClassName): void
	{
		// This test expects an exception during container initialization
		if ($testClassName === InvalidIgnoredErrorExceptionTest::class) {
			return;
		}

		$testClassName::getContainer();
	}

}
