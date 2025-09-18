<?php declare(strict_types = 1);

namespace PHPStan\Testing\PHPUnit;

use PHPStan\DependencyInjection\InvalidIgnoredErrorExceptionTest;

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
