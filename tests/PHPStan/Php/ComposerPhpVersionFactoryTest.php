<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class ComposerPhpVersionFactoryTest extends TestCase
{

	public static function dataVersions(): iterable
	{
		yield ['>8.2.3 <8.5.2', 80204, 80501];
		yield ['>=8.2.3 <=8.5.2', 80203, 80502];
		yield ['8.4.*', 80400, 80499];
		yield ['~8.1.0', 80100, 80199];
		yield ['>=8.1 <8.3', 80100, 80299];
		yield ['>8.2.99', 80300, PhpVersionFactory::MAX_PHP_VERSION];
		yield ['>8.2.3-beta', 80203, PhpVersionFactory::MAX_PHP_VERSION];
		yield ['<8.5.2.1', null, 80502];
		yield ['<8.4', null, 80399];
		yield ['^7.4', 70400, PhpVersionFactory::MAX_PHP7_VERSION];
		yield ['^5.6', 50600, PhpVersionFactory::MAX_PHP5_VERSION];
		yield ['^8.0', 80000, PhpVersionFactory::MAX_PHP_VERSION];
		yield ['^7.4 || ^8.0', 70400, PhpVersionFactory::MAX_PHP_VERSION];
	}

	#[DataProvider('dataVersions')]
	public function testVersions(string $constraint, ?int $expectedMin, int $expectedMax): void
	{
		$dir = sys_get_temp_dir() . '/phpstan-composer-php-version-' . uniqid();
		mkdir($dir);
		file_put_contents($dir . '/composer.json', sprintf('{"require": {"php": "%s"}}', $constraint));

		try {
			$factory = new ComposerPhpVersionFactory([$dir]);
			$this->assertSame($expectedMin, $factory->getMinVersion()?->getVersionId());
			$this->assertSame($expectedMax, $factory->getMaxVersion()?->getVersionId());
		} finally {
			unlink($dir . '/composer.json');
			rmdir($dir);
		}
	}

}
