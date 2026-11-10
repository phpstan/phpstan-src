<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use function ltrim;
use function strtolower;

/**
 * Whether a function exists - the value of a function looked up and not found, see
 * ValueDependencyCollector::recordMissingSymbol(). What a function that exists declares is
 * followed by the files depending on its file instead.
 *
 * The key is the fully qualified name, lowercased like PHP compares function names.
 */
#[AutowiredService]
final class FunctionResultCacheValueExtension implements ResultCacheValueExtension
{

	private const FOUND = 'found';

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public static function createKey(string $functionName): string
	{
		return strtolower(ltrim($functionName, '\\'));
	}

	public function getValue(string $key): string
	{
		if ($key === '') {
			return ValueDependencyCollector::MISSING;
		}

		return $this->reflectionProvider->hasFunction(new FullyQualified($key), null)
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
