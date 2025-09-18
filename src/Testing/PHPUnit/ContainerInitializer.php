<?php

declare(strict_types=1);

namespace PHPStan\Testing\PHPUnit;

final class ContainerInitializer {
	/**
	 * @var array<string, true>
	 */
	private static $initialized = [];

	static public function initialize(string $testClassName): void
	{
		if (array_key_exists($testClassName, self::$initialized)) {
			return;
		}

		$testClassName::getContainer();

		self::$initialized[$testClassName] = true;
	}
}
