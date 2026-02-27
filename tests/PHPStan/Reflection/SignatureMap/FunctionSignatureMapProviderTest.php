<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use function memory_get_usage;

class FunctionSignatureMapProviderTest extends PHPStanTestCase
{

	/**
	 * Regression test for https://github.com/phpstan/phpstan/issues/10039
	 *
	 * The signature map and function metadata should be cached in static properties
	 * so the large functionMap.php file is loaded once and shared across all instances.
	 * Without static caching, each new DI container (e.g., in test suites) would reload
	 * the entire ~7MB functionMap.php, causing excessive memory usage.
	 */
	public function testBug10039(): void
	{
		$parser = self::getContainer()->getByType(SignatureMapParser::class);
		$initializerResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		$phpVersion = new PhpVersion(80200);

		// Create first instance and load the signature map
		$provider1 = new FunctionSignatureMapProvider($parser, $initializerResolver, $phpVersion, false);
		$provider1->getSignatureMap();

		// Create a second instance with the same parameters
		$provider2 = new FunctionSignatureMapProvider($parser, $initializerResolver, $phpVersion, false);

		// With static caching, the second call should not allocate significant memory
		// because it returns the already-cached map. Without static caching (the bug),
		// each instance would load and process the entire functionMap.php (~7MB).
		$memBefore = memory_get_usage();
		$provider2->getSignatureMap();
		$memDiff = memory_get_usage() - $memBefore;

		$this->assertLessThan(
			1024 * 1024, // 1 MB - the full load uses ~7 MB
			$memDiff,
			'Second FunctionSignatureMapProvider instance should reuse the static cache, not reload functionMap.php',
		);
	}

}
