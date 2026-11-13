<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceStubber\ExtensionVersionProvider;
use function array_key_exists;
use function array_keys;
use function is_array;
use function is_file;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * Caches the reflections built from the PhpStorm stubs the way the optimized
 * locators cache userland ones. PHP built-in classes never had a cache, so
 * every process parsed the stub files and rebuilt the same reflections again;
 * with this in place they are built once, persisted like any other cache
 * entry, and shared across a run's parallel workers through the arena.
 *
 * The stubber's output depends on the stubs package, the reflection library
 * and the target PHP and extension versions (stub members are version-filtered),
 * so all four are part of the cache key.
 *
 * The exported blob contains the absolute path of the stub file, but the
 * cache key deliberately contains no paths - the same entries are shared by
 * every PHPStan installation with the same package versions, and survive the
 * installation being moved. The path is therefore stored relative to the
 * phpstorm-stubs package root and resolved against the current installation
 * on import (https://github.com/phpstan/phpstan/issues/15023).
 */
final class CachedPhpInternalSourceLocator implements SourceLocator
{

	private const STUBS_DIR_MARKER = '/jetbrains/phpstorm-stubs/';

	private const RELATIVE_FILENAME_PREFIX = 'phpstorm-stubs:';

	/** @var array<string, true>|null lowercase names of the functions in the stubs not listed in lowercase in PhpStormStubsMap::FUNCTIONS, see isStubFunction() */
	private static ?array $otherStubFunctions = null;

	private ?string $variableCacheKey = null;

	private ?string $stubsRootDir = null;

	public function __construct(
		private SourceLocator $inner,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private PhpStormStubsSourceStubber $stubber,
		private ExtensionVersionProvider $extensionVersionProvider,
	)
	{
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$name = $identifier->getName();
		if ($identifier->isClass() || $identifier->isFunction()) {
			$name = strtolower($name);
		}

		// Almost everything that reaches this locator is a userland class or
		// function name that every earlier locator failed to resolve - also every
		// namespaced name an unqualified function call is looked up by first -
		// and a name the stubs do not have can never have been cached - the
		// stubber is the only source the wrapped locator reads from. Ruling those
		// out against the stubs map is an array lookup, where asking the cache
		// means hashing the key and stat()ing a file that will never exist. The
		// wrapped locator is still asked: it resolves class aliases before
		// consulting the stubber, so it can answer for a name the map does not
		// list - such an answer just does not get cached, under a key that would
		// be the alias anyway.
		if (
			($identifier->isClass() && !$this->stubber->hasClass($name))
			|| ($identifier->isFunction() && !self::isStubFunction($name))
		) {
			return $this->inner->locateIdentifier($reflector, $identifier);
		}

		$cacheKey = sprintf('phpinternal-%s-%s', $identifier->getType()->getName(), $name);
		$variableCacheKey = $this->getVariableCacheKey();

		$cached = $this->cache->load($cacheKey, $variableCacheKey);
		if (is_array($cached)) {
			$cached = $this->resolveStubFilename($cached);
		}
		if (is_array($cached)) {
			if ($identifier->isConstant()) {
				return ReflectionConstant::importFromCache($reflector, $cached);
			}
			if ($identifier->isFunction()) {
				return ReflectionFunction::importFromCache($reflector, $cached);
			}
			if ($identifier->isClass()) {
				if (array_key_exists('backingType', $cached)) {
					return ReflectionEnum::importFromCache($reflector, $cached);
				}

				return ReflectionClass::importFromCache($reflector, $cached);
			}
		}

		$reflection = $this->inner->locateIdentifier($reflector, $identifier);
		if (
			$reflection instanceof ReflectionClass
			|| $reflection instanceof ReflectionFunction
			|| $reflection instanceof ReflectionConstant
		) {
			$exported = $this->relativizeStubFilename($reflection->exportToCache());
			if ($exported !== null) {
				$this->cache->save($cacheKey, $variableCacheKey, $exported);
			}
		}

		return $reflection;
	}

	/**
	 * @param array<string, mixed> $exported
	 * @return array<string, mixed>|null
	 */
	private function relativizeStubFilename(array $exported): ?array
	{
		$filename = $exported['locatedSource']['data']['filename'] ?? null;
		if (!is_string($filename)) {
			return null;
		}

		$markerPosition = strpos($filename, self::STUBS_DIR_MARKER);
		if ($markerPosition === false) {
			return null;
		}

		$exported['locatedSource']['data']['filename'] = self::RELATIVE_FILENAME_PREFIX . substr($filename, $markerPosition + strlen(self::STUBS_DIR_MARKER));

		return $exported;
	}

	/**
	 * @param array<string, mixed> $cached
	 * @return array<string, mixed>|null
	 */
	private function resolveStubFilename(array $cached): ?array
	{
		$filename = $cached['locatedSource']['data']['filename'] ?? null;
		if (!is_string($filename) || !str_starts_with($filename, self::RELATIVE_FILENAME_PREFIX)) {
			return null;
		}

		$resolved = ($this->stubsRootDir ??= ComposerHelper::getPhpStormStubsDir()) . '/' . substr($filename, strlen(self::RELATIVE_FILENAME_PREFIX));
		if (!is_file($resolved)) {
			return null;
		}

		$cached['locatedSource']['data']['filename'] = $resolved;

		return $cached;
	}

	/**
	 * Whether the stubber could have a function: the stubber has no cheap way to tell, its own
	 * answer parses the stub file. The functions of the extension versions it selects are added
	 * to the default ones, so they are all included.
	 *
	 * Nearly all names in the map are lowercase already and are looked up in it directly: a
	 * lowercased copy of the whole map would stay in memory for the rest of the process.
	 */
	private static function isStubFunction(string $lowercaseName): bool
	{
		if (array_key_exists($lowercaseName, PhpStormStubsMap::FUNCTIONS)) {
			return true;
		}

		if (self::$otherStubFunctions === null) {
			$functions = [];
			foreach (array_keys(PhpStormStubsMap::FUNCTIONS) as $name) {
				$lowercaseFunctionName = strtolower($name);
				if ($lowercaseFunctionName === $name) {
					continue;
				}

				$functions[$lowercaseFunctionName] = true;
			}
			foreach (PhpStormStubsMap::EXTENSION_VERSIONS as $versionMaps) {
				foreach ($versionMaps as $versionMap) {
					foreach (array_keys($versionMap['functions']) as $name) {
						$functions[strtolower($name)] = true;
					}
				}
			}
			self::$otherStubFunctions = $functions;
		}

		return array_key_exists($lowercaseName, self::$otherStubFunctions);
	}

	/**
	 * @return list<Reflection>
	 */
	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->inner->locateIdentifiersByType($reflector, $identifierType);
	}

	private function getVariableCacheKey(): string
	{
		return $this->variableCacheKey ??= sprintf(
			'v3-%s-%s-%s-%s',
			ComposerHelper::getBetterReflectionVersion(),
			ComposerHelper::getPhpStormStubsVersion(),
			$this->phpVersion->getVersionString(),
			$this->extensionVersionProvider->getCacheKey(),
		);
	}

}
