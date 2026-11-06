<?php declare(strict_types = 1);

namespace PHPStan\Testing\PHPUnit;

use PHPStan\DependencyInjection\InvalidIgnoredErrorExceptionTest;
use PHPStan\DependencyInjection\InvalidSuperglobalTypesTest;
use function in_array;

final class ContainerInitializer
{

	public static function initialize(string $testClassName): void
	{
		// These tests expect an exception during container initialization
		if (in_array($testClassName, [InvalidIgnoredErrorExceptionTest::class, InvalidSuperglobalTypesTest::class], true)) {
			return;
		}

		$testClassName::getContainer();
	}

}
