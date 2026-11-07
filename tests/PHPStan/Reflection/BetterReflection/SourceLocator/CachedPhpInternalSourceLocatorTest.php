<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\Cache\CacheStorage;
use PHPStan\Cache\MemoryCacheStorage;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use PHPStan\Testing\PHPStanTestCase;
use function array_walk_recursive;
use function is_string;
use function strpos;
use function strtolower;
use function substr;

class CachedPhpInternalSourceLocatorTest extends PHPStanTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/data/php-8.4.neon'];
	}

	/**
	 * The methods an enum gets from the engine (cases(), from(), tryFrom())
	 * carry their own located source in the cache entry, next to the enum's.
	 */
	public function testEnumFromCacheFilledByAnotherInstallation(): void
	{
		$storage = new MemoryCacheStorage();
		$this->createReflector($this->createStorageOfAnotherInstallation($storage))->reflectClass('RoundingMode');

		$enum = $this->createReflector($storage, 'RoundingMode')->reflectClass('RoundingMode');
		$this->assertInstanceOf(ReflectionEnum::class, $enum);

		$method = $enum->getMethod('cases');
		$this->assertNotNull($method);

		$fileName = $method->getFileName();
		$this->assertNotNull($fileName);
		$this->assertFileExists($fileName);
	}

	/**
	 * @param string|null $cachedOnlyClassName a class that the stubs locator does not find, so it has to come from the cache
	 */
	private function createReflector(CacheStorage $storage, ?string $cachedOnlyClassName = null): Reflector
	{
		$container = self::getContainer();
		$stubber = $container->getByType(PhpStormStubsSourceStubber::class);

		$stubsLocator = new PhpInternalSourceLocator(new Locator($container->getService('php8PhpParser')), $stubber);
		if ($cachedOnlyClassName !== null) {
			$stubsLocator = $this->createLocatorWithoutClass($stubsLocator, $cachedOnlyClassName);
		}

		return new DefaultReflector(new CachedPhpInternalSourceLocator(
			$stubsLocator,
			new Cache($storage),
			$container->getByType(PhpVersion::class),
			$stubber,
			$container->getByType(ExtensionVersionProvider::class),
		));
	}

	private function createLocatorWithoutClass(SourceLocator $locator, string $className): SourceLocator
	{
		return new class ($locator, $className) implements SourceLocator {

			public function __construct(private SourceLocator $locator, private string $className)
			{
			}

			#[Override]
			public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
			{
				if ($identifier->isClass() && strtolower($identifier->getName()) === strtolower($this->className)) {
					return null;
				}

				return $this->locator->locateIdentifier($reflector, $identifier);
			}

			#[Override]
			public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
			{
				return $this->locator->locateIdentifiersByType($reflector, $identifierType);
			}

		};
	}

	/**
	 * Saves the entries with the stub paths of an installation that has since
	 * been moved or deleted, like a cache directory shared between CI jobs
	 * that check out the project into different directories.
	 */
	private function createStorageOfAnotherInstallation(CacheStorage $storage): CacheStorage
	{
		return new class ($storage) implements CacheStorage {

			public function __construct(private CacheStorage $storage)
			{
			}

			#[Override]
			public function load(string $key, string $variableKey)
			{
				return $this->storage->load($key, $variableKey);
			}

			#[Override]
			public function save(string $key, string $variableKey, $data): void
			{
				array_walk_recursive($data, static function (&$value): void {
					if (!is_string($value)) {
						return;
					}

					$markerPosition = strpos($value, '/jetbrains/phpstorm-stubs/');
					if ($markerPosition === false) {
						return;
					}

					$value = '/moved-away/vendor' . substr($value, $markerPosition);
				});

				$this->storage->save($key, $variableKey, $data);
			}

		};
	}

}
