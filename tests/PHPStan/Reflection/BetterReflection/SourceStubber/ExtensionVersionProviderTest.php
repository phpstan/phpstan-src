<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\ExtDsStubFilesExtension;
use PHPStan\Testing\PHPStanTestCase;

class ExtensionVersionProviderTest extends PHPStanTestCase
{

	public function testSelectsPlatformExtensionVersion(): void
	{
		$provider = new ExtensionVersionProvider([__DIR__ . '/data/ext-ds-v2-platform']);

		$this->assertSame(['ds' => 2], $provider->getExtensionVersions());
		$this->assertSame('ds:2', $provider->getCacheKey());
	}

	public function testSelectsExtensionVersionFromRequireConstraint(): void
	{
		$provider = new ExtensionVersionProvider([__DIR__ . '/data/ext-ds-v2-require']);

		$this->assertSame(['ds' => 2], $provider->getExtensionVersions());
	}

	public function testPassesSelectedVersionToPhpStormStubsSourceStubber(): void
	{
		$provider = new ExtensionVersionProvider([__DIR__ . '/data/ext-ds-v2-platform']);
		$factory = new PhpStormStubsSourceStubberFactory(
			self::getContainer()->getService('php8PhpParser'),
			self::getContainer()->getByType(Printer::class),
			new PhpVersion(80200),
			0,
			$provider,
		);

		$sourceStubber = $factory->create();
		$this->assertFalse($sourceStubber->hasClass('Ds\\Vector'));
		$this->assertTrue($sourceStubber->hasClass('Ds\\Seq'));
	}

	public function testDoesNotLoadExtDsV1OverlayForV2(): void
	{
		$extension = new ExtDsStubFilesExtension(new ExtensionVersionProvider([__DIR__ . '/data/ext-ds-v2-platform']));

		$this->assertSame([], $extension->getFiles());
	}

	public function testLoadsExtDsV1OverlayForV1(): void
	{
		$extension = new ExtDsStubFilesExtension(new ExtensionVersionProvider([__DIR__ . '/data/ext-ds-v1-platform']));

		$files = $extension->getFiles();
		$this->assertCount(1, $files);
		$this->assertStringEndsWith('/stubs/ext-ds.stub', $files[0]);
	}

}
