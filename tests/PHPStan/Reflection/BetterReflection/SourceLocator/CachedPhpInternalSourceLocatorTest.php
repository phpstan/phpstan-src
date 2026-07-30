<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Override;
use PhpParser\Parser;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function is_file;
use function str_starts_with;

class CachedPhpInternalSourceLocatorTest extends PHPStanTestCase
{

	public static function dataIdentifiers(): iterable
	{
		yield 'class' => [new Identifier('Countable', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))];
		yield 'function' => [new Identifier('strlen', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))];
		yield 'constant' => [new Identifier('PREG_PATTERN_ORDER', new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT))];
	}

	private static function createEnumIdentifier(): Identifier
	{
		// Random\IntervalBoundary is the first enum bundled with PHP (8.3);
		// under older versions the stubber filters it out
		return new Identifier('Random\IntervalBoundary', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
	}

	#[RequiresPhp('>= 8.3.0')]
	public function testPersistedEnumEntryIsPortableAcrossInstallations(): void
	{
		$this->testPersistedEntryIsPortableAcrossInstallations(self::createEnumIdentifier());
	}

	#[RequiresPhp('>= 8.3.0')]
	public function testWarmCacheEnumImportResolvesAgainstCurrentInstallation(): void
	{
		$this->testWarmCacheImportResolvesAgainstCurrentInstallation(self::createEnumIdentifier());
	}

	#[RequiresPhp('>= 8.3.0')]
	public function testEnumEntryWithUnresolvableStubPathIsTreatedAsCacheMiss(): void
	{
		$this->testEntryWithUnresolvableStubPathIsTreatedAsCacheMiss(self::createEnumIdentifier());
	}

	/**
	 * The cache key is environment-independent, so an entry written by one
	 * installation may be read by another where the project is mounted at a
	 * different absolute path (host vs. Docker container). The persisted
	 * blob must therefore not contain this installation's absolute paths.
	 */
	#[DataProvider('dataIdentifiers')]
	public function testPersistedEntryIsPortableAcrossInstallations(Identifier $identifier): void
	{
		$storage = $this->createSpyStorage();
		$locator = $this->createLocator($this->createInnerLocator(), $storage);
		$reflection = $locator->locateIdentifier(new DefaultReflector($locator), $identifier);

		$this->assertInstanceOf(Reflection::class, $reflection);
		$this->assertNotEmpty($storage->items);

		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$stubsDirectory = $fileHelper->normalizePath(PhpStormStubsMap::DIR, '/');
		foreach ($storage->items as $key => $item) {
			$filename = $item['locatedSource']['data']['filename'];
			$this->assertIsString($filename);
			$this->assertFalse(str_starts_with($filename, '/'), $key . ': stored stub path must be relative, got ' . $filename);
			$this->assertStringNotContainsString('://', $filename, $key . ': stored stub path must be relative, got ' . $filename);
			$this->assertTrue(is_file($stubsDirectory . '/' . $filename), $key . ': stored stub path must resolve against the stubs directory, got ' . $filename);
		}
	}

	#[DataProvider('dataIdentifiers')]
	public function testWarmCacheImportResolvesAgainstCurrentInstallation(Identifier $identifier): void
	{
		$storage = $this->createSpyStorage();
		$coldLocator = $this->createLocator($this->createInnerLocator(), $storage);
		$coldReflection = $coldLocator->locateIdentifier(new DefaultReflector($coldLocator), $identifier);
		$this->assertInstanceOf(Reflection::class, $coldReflection);

		$innerMustNotBeHit = new class ($this) implements SourceLocator {

			public function __construct(private CachedPhpInternalSourceLocatorTest $test)
			{
			}

			#[Override]
			public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
			{
				$this->test->fail('Warm cache must not fall back to the inner locator');
			}

			#[Override]
			public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
			{
				return [];
			}

		};

		$warmLocator = $this->createLocator($innerMustNotBeHit, $storage);
		$reflection = $warmLocator->locateIdentifier(new DefaultReflector($warmLocator), $identifier);

		$this->assertInstanceOf(Reflection::class, $reflection);
		$this->assertSame($identifier->getName(), $reflection->getName());

		if (
			!$reflection instanceof ReflectionClass
			&& !$reflection instanceof ReflectionFunction
			&& !$reflection instanceof ReflectionConstant
		) {
			$this->fail('Unexpected reflection class ' . $reflection::class);
		}

		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$stubsDirectory = $fileHelper->normalizePath(PhpStormStubsMap::DIR, '/');
		$fileName = $reflection->getLocatedSource()->getFileName();
		$this->assertNotNull($fileName);
		$this->assertTrue(str_starts_with($fileHelper->normalizePath($fileName, '/'), $stubsDirectory . '/'), 'Imported stub path must point into this installation, got ' . $fileName);
	}

	/**
	 * An entry whose stub file does not exist in this installation (written
	 * by a distribution with a different stub layout, e.g. phar vs. vendor)
	 * must be treated as a cache miss instead of crashing the analysis.
	 */
	#[DataProvider('dataIdentifiers')]
	public function testEntryWithUnresolvableStubPathIsTreatedAsCacheMiss(Identifier $identifier): void
	{
		$storage = $this->createSpyStorage();
		$coldLocator = $this->createLocator($this->createInnerLocator(), $storage);
		$coldReflection = $coldLocator->locateIdentifier(new DefaultReflector($coldLocator), $identifier);
		$this->assertInstanceOf(Reflection::class, $coldReflection);

		foreach ($storage->items as $key => $item) {
			$item['locatedSource']['data']['filename'] = 'Core/DoesNotExist.php';
			$storage->items[$key] = $item;
		}

		$locator = $this->createLocator($this->createInnerLocator(), $storage);
		$reflection = $locator->locateIdentifier(new DefaultReflector($locator), $identifier);

		$this->assertInstanceOf(Reflection::class, $reflection);
		$this->assertSame($identifier->getName(), $reflection->getName());
	}

	private function createLocator(SourceLocator $inner, SpyCacheStorage $storage): CachedPhpInternalSourceLocator
	{
		return new CachedPhpInternalSourceLocator(
			$inner,
			new Cache($storage),
			self::getContainer()->getByType(PhpVersion::class),
			self::getContainer()->getByType(FileHelper::class),
		);
	}

	private function createInnerLocator(): PhpInternalSourceLocator
	{
		$php8Parser = self::getContainer()->getService('php8PhpParser');
		if (!$php8Parser instanceof Parser) {
			throw new ShouldNotHappenException();
		}

		return new PhpInternalSourceLocator(
			new Locator($php8Parser),
			self::getContainer()->getByType(PhpStormStubsSourceStubber::class),
		);
	}

	private function createSpyStorage(): SpyCacheStorage
	{
		return new SpyCacheStorage();
	}

}
