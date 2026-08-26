<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\Cache\CacheStorage;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use PHPStan\Testing\PHPStanTestCase;
use function str_ends_with;

class CachedPhpInternalSourceLocatorTest extends PHPStanTestCase
{

	public function testExtensionVersionsArePartOfVariableCacheKey(): void
	{
		$variableKeys = [];
		$storage = $this->createMock(CacheStorage::class);
		$storage->expects($this->exactly(2))
			->method('load')
			->willReturnCallback(static function (string $key, string $variableKey) use (&$variableKeys) {
				$variableKeys[] = $variableKey;
				return null;
			});

		$cache = new Cache($storage);
		$inner = $this->createStub(SourceLocator::class);
		$reflector = $this->createStub(Reflector::class);
		$identifier = new Identifier('CacheSeparationFixture', new IdentifierType());

		foreach (['ext-ds-v1-platform', 'ext-ds-v2-platform'] as $fixture) {
			$locator = new CachedPhpInternalSourceLocator(
				$inner,
				$cache,
				new PhpVersion(80200),
				new ExtensionVersionProvider([__DIR__ . '/../SourceStubber/data/' . $fixture]),
			);
			$locator->locateIdentifier($reflector, $identifier);
		}

		$this->assertCount(2, $variableKeys);
		$this->assertNotSame($variableKeys[0], $variableKeys[1]);
		$this->assertTrue(str_ends_with($variableKeys[0], '-ds:1'));
		$this->assertTrue(str_ends_with($variableKeys[1], '-ds:2'));
	}

}
