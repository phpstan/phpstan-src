<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Testing\PHPStanTestCase;

final class ClassResultCacheValueExtensionTest extends PHPStanTestCase
{

	public function testValue(): void
	{
		$extension = self::getContainer()->getByType(ClassResultCacheValueExtension::class);

		$this->assertSame('missing', $extension->getValue(ClassResultCacheValueExtension::createKey('PHPStan\Analyser\ResultCache\NoSuchClass')));

		$key = ClassResultCacheValueExtension::createKey('\\' . self::class);
		$this->assertSame(self::class, $key);
		$value = $extension->getValue($key);
		$this->assertNotSame('missing', $value);
		$this->assertSame($value, $extension->getValue($key));
		$this->assertNotSame($value, $extension->getValue(ClassResultCacheValueExtension::createKey(DirectoryResultCacheValueExtensionTest::class)));
	}

}
