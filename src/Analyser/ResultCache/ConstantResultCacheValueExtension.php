<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use function ltrim;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Whether a global constant exists - the value of a constant looked up and not found, see
 * ValueDependencyCollector::recordMissingSymbol(). What a constant that exists is declared as is
 * followed by the files depending on its file instead.
 *
 * The key is the fully qualified name with the namespace lowercased, like PHP compares constant
 * names: the namespace case-insensitively, the name itself case-sensitively.
 */
#[AutowiredService]
final class ConstantResultCacheValueExtension implements ResultCacheValueExtension
{

	private const FOUND = 'found';

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public static function createKey(string $constantName): string
	{
		$constantName = ltrim($constantName, '\\');
		$lastBackslash = strrpos($constantName, '\\');
		if ($lastBackslash === false) {
			return $constantName;
		}

		return strtolower(substr($constantName, 0, $lastBackslash)) . substr($constantName, $lastBackslash);
	}

	public function getValue(string $key): string
	{
		if ($key === '') {
			return ValueDependencyCollector::MISSING;
		}

		return $this->reflectionProvider->hasConstant(new FullyQualified($key), null)
			? self::FOUND
			: ValueDependencyCollector::MISSING;
	}

	public function keyToResultCache(string $key): string
	{
		return $key;
	}

	public function keyFromResultCache(string $storedKey): string
	{
		return $storedKey;
	}

}
