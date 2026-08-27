<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use PHPStan\File\FileHelper;
use PHPUnit\Framework\TestCase;
use function array_keys;
use function array_values;
use function dirname;
use function hash_file;
use function str_starts_with;
use const PHP_VERSION_ID;

class AttributeServicesDirectoriesResolverTest extends TestCase
{

	private static function getRepoRoot(): string
	{
		return dirname(__DIR__, 4);
	}

	private function createResolver(int $phpVersionId = PHP_VERSION_ID): AttributeServicesDirectoriesResolver
	{
		$repoRoot = self::getRepoRoot();

		return new AttributeServicesDirectoriesResolver(new FileHelper($repoRoot), [$repoRoot], $phpVersionId);
	}

	public function testEmptySection(): void
	{
		$resolver = $this->createResolver();
		$this->assertSame([], $resolver->resolve(null)->directories);
		$this->assertSame([], $resolver->resolve([])->directories);
	}

	public function testPhpVersionGate(): void
	{
		$resolver = $this->createResolver(70428);
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('The attributeServicesDirectories section requires PHP 8.0 or later, PHPStan is running on PHP 7.4.28.');
		$resolver->resolve([__DIR__]);
	}

	public function testNonListSection(): void
	{
		$resolver = $this->createResolver();
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('The attributeServicesDirectories section must contain a list of directory paths.');
		$resolver->resolve('src');
	}

	public function testNonStringEntry(): void
	{
		$resolver = $this->createResolver();
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('The attributeServicesDirectories section must contain a list of directory paths.');
		$resolver->resolve([['src']]);
	}

	public function testParameterEntryRejected(): void
	{
		$resolver = $this->createResolver();
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('Entry %rootDir%/src in the attributeServicesDirectories section must be a plain path - % parameters are not supported.');
		$resolver->resolve(['%rootDir%/src']);
	}

	public function testWildcardEntryRejected(): void
	{
		$resolver = $this->createResolver();
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('Entry */src in the attributeServicesDirectories section must be a plain path - wildcards are not supported.');
		$resolver->resolve(['*/src']);
	}

	public function testMissingDirectory(): void
	{
		$resolver = $this->createResolver();
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('does not exist');
		$resolver->resolve([__DIR__ . '/does-not-exist']);
	}

	public function testDirectoryOutsideComposerProjects(): void
	{
		$resolver = new AttributeServicesDirectoriesResolver(new FileHelper(self::getRepoRoot()), []);
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('is not inside any project with Composer metadata known to PHPStan');
		$resolver->resolve([__DIR__]);
	}

	public function testProjectOwnPsr4Directory(): void
	{
		$repoRoot = self::getRepoRoot();
		$directory = $repoRoot . '/src/DependencyInjection/AttributeServices';
		$resolved = $this->createResolver()->resolve([$directory]);

		$this->assertCount(1, $resolved->directories);
		$resolvedDirectory = $resolved->directories[0];
		$this->assertNull($resolvedDirectory->packageName);
		$this->assertArrayHasKey('PHPStan\\', $resolvedDirectory->psr4);
		$this->assertContains($repoRoot . '/src', $resolvedDirectory->psr4['PHPStan\\']);

		$expectedFile = $directory . '/AttributeServicesDirectoriesResolver.php';
		$this->assertArrayHasKey($expectedFile, $resolvedDirectory->cacheKeyComponent);
		$this->assertSame(hash_file('sha256', $expectedFile), $resolvedDirectory->cacheKeyComponent[$expectedFile]);
	}

	public function testProjectOwnClassmapDirectoryFromAutoloadDev(): void
	{
		$repoRoot = self::getRepoRoot();
		$resolved = $this->createResolver()->resolve([__DIR__]);

		$this->assertCount(1, $resolved->directories);
		$resolvedDirectory = $resolved->directories[0];
		$this->assertNull($resolvedDirectory->packageName);
		$this->assertContains($repoRoot . '/tests/PHPStan', $resolvedDirectory->classmapPaths);
		$this->assertSame($repoRoot . '/vendor/composer/autoload_classmap.php', $resolvedDirectory->autoloadClassmapPath);
	}

	public function testVendorPackageDirectory(): void
	{
		$repoRoot = self::getRepoRoot();
		$directory = $repoRoot . '/vendor/nikic/php-parser/lib/PhpParser';
		$resolved = $this->createResolver()->resolve([$directory]);

		$this->assertCount(1, $resolved->directories);
		$resolvedDirectory = $resolved->directories[0];
		$this->assertSame('nikic/php-parser', $resolvedDirectory->packageName);

		$this->assertSame([$directory], array_keys($resolvedDirectory->cacheKeyComponent));
		$this->assertTrue(str_starts_with(array_values($resolvedDirectory->cacheKeyComponent)[0], 'package:nikic/php-parser:'));
	}

	public function testUncoveredDirectory(): void
	{
		$resolver = $this->createResolver();
		$this->expectException(InvalidAttributeServicesDirectoriesException::class);
		$this->expectExceptionMessage('is not covered by the autoload section of');
		$resolver->resolve([self::getRepoRoot() . '/bin']);
	}

	public function testNestedDirectoriesDeduplicated(): void
	{
		$repoRoot = self::getRepoRoot();
		$resolved = $this->createResolver()->resolve([
			$repoRoot . '/src/DependencyInjection/AttributeServices',
			$repoRoot . '/src/DependencyInjection',
		]);

		$this->assertCount(1, $resolved->directories);
		$this->assertSame($repoRoot . '/src/DependencyInjection', $resolved->directories[0]->directory);
	}

	public function testAllErrorsAreCollected(): void
	{
		$resolver = $this->createResolver();
		try {
			$resolver->resolve([
				__DIR__ . '/does-not-exist',
				self::getRepoRoot() . '/bin',
			]);
			$this->fail('Expected InvalidAttributeServicesDirectoriesException.');
		} catch (InvalidAttributeServicesDirectoriesException $e) {
			$this->assertCount(2, $e->getErrors());
		}
	}

}
