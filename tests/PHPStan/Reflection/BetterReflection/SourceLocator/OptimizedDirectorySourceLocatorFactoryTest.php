<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\Cache\Cache;
use PHPStan\Cache\MemoryCacheStorage;
use PHPStan\File\FileContentHasher;
use PHPStan\File\FileStatSignatures;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use function clearstatcache;
use function file_put_contents;
use function max;
use function mkdir;
use function sprintf;
use function stat;
use function sys_get_temp_dir;
use function time;
use function uniqid;
use function usleep;
use const DIRECTORY_SEPARATOR;

final class OptimizedDirectorySourceLocatorFactoryTest extends PHPStanTestCase
{

	private const CACHE_KEY = 'odsl-factory-test';

	private string $directory;

	private Cache $cache;

	protected function setUp(): void
	{
		parent::setUp();

		$this->directory = sys_get_temp_dir() . '/' . uniqid('phpstan-odsl-', true);
		mkdir($this->directory);
		$this->cache = new Cache(new MemoryCacheStorage());
	}

	public function testChangedFileIsScannedAgain(): void
	{
		file_put_contents($this->directory . '/a.php', '<?php namespace OdslFactoryTest; class First {}');
		$this->assertTrue($this->hasClass('OdslFactoryTest\\First'));

		// the same second, so the signature cannot tell - the content hash does
		file_put_contents($this->directory . '/a.php', '<?php namespace OdslFactoryTest; class Second {}');
		clearstatcache();

		$this->assertFalse($this->hasClass('OdslFactoryTest\\First'));
		$this->assertTrue($this->hasClass('OdslFactoryTest\\Second'));
	}

	public function testUnchangedFileIsTrustedByItsSignature(): void
	{
		if (DIRECTORY_SEPARATOR !== '/') {
			$this->markTestSkipped('Signatures are not trusted on Windows.');
		}

		file_put_contents($this->directory . '/a.php', '<?php namespace OdslFactoryTest; class First {}');
		$this->waitUntilInThePast($this->directory . '/a.php');
		$this->assertTrue($this->hasClass('OdslFactoryTest\\First'));

		$entry = $this->cache->load(self::CACHE_KEY, $this->getVariableCacheKey());
		$this->assertIsArray($entry);
		$this->assertNotNull($entry[$this->directory . '/a.php'][1]);

		// a hash that cannot match: the entry is only reused because the signature does
		$entry[$this->directory . '/a.php'][0] = 'not-the-hash';
		$this->cache->save(self::CACHE_KEY, $this->getVariableCacheKey(), $entry);
		$this->assertTrue($this->hasClass('OdslFactoryTest\\First'));
		$this->assertSame($entry, $this->cache->load(self::CACHE_KEY, $this->getVariableCacheKey()));

		file_put_contents($this->directory . '/a.php', '<?php namespace OdslFactoryTest; class SecondWithLongerName {}');
		clearstatcache();

		$this->assertFalse($this->hasClass('OdslFactoryTest\\First'));
		$this->assertTrue($this->hasClass('OdslFactoryTest\\SecondWithLongerName'));
	}

	public function testBatchedScanFillsEveryLocator(): void
	{
		mkdir($this->directory . '/sub');
		file_put_contents($this->directory . '/a.php', '<?php namespace OdslFactoryTest; class First {}');
		file_put_contents($this->directory . '/sub/b.php', '<?php namespace OdslFactoryTest; class Second {}');

		$factory = $this->createFactory();
		$factory->beginBatchedScan();
		$all = $factory->createByFiles([$this->directory . '/a.php', $this->directory . '/sub/b.php'], 'odsl-factory-test-all');
		$sub = $factory->createByFiles([$this->directory . '/sub/b.php'], 'odsl-factory-test-sub');
		$factory->flushBatchedScan();

		$this->assertCount(2, $all->locateIdentifiersByType(new DefaultReflector($all), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
		$this->assertCount(1, $sub->locateIdentifiersByType(new DefaultReflector($sub), new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));
	}

	private function hasClass(string $className): bool
	{
		// a new factory is a new run: nothing but the cache is shared
		$locator = $this->createFactory()->createByFiles([$this->directory . '/a.php'], self::CACHE_KEY);

		return $locator->locateIdentifier(new DefaultReflector($locator), new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))) !== null;
	}

	private function createFactory(): OptimizedDirectorySourceLocatorFactory
	{
		$container = self::getContainer();

		return new OptimizedDirectorySourceLocatorFactory(
			$container->getByType(FileNodesFetcher::class),
			$container->getService('fileFinderScan'),
			$container->getByType(PhpVersion::class),
			$container->getByType(SymbolFinderInFiles::class),
			$this->cache,
			new FileContentHasher(),
			new FileStatSignatures(),
			$this->directory . '-tmp',
		);
	}

	private function getVariableCacheKey(): string
	{
		return sprintf('v2-%s', self::getContainer()->getByType(PhpVersion::class)->supportsEnums() ? 'enums' : 'no-enums');
	}

	private function waitUntilInThePast(string $file): void
	{
		clearstatcache();
		$stat = stat($file);
		$this->assertIsArray($stat);
		$latest = max($stat['mtime'], $stat['ctime']);
		while (time() <= $latest) {
			usleep(50_000);
		}
	}

}
