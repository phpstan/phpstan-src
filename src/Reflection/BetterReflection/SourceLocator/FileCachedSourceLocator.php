<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Const_;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConstantTypeHelper;
use ReflectionClass;
use ReflectionFunction;
use function array_key_exists;
use function array_keys;
use function class_exists;
use function constant;
use function count;
use function defined;
use function function_exists;
use function interface_exists;
use function is_file;
use function is_string;
use function opcache_invalidate;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_functions;
use function strtolower;
use function trait_exists;
use const PHP_VERSION_ID;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *
 * Modified code from Roave/BetterReflection, Copyright (c) 2017 Roave, LLC.
 */
final class FileCachedSourceLocator implements SourceLocator
{
	/** @var array<string, mixed> */
	private array $cached;

	public function __construct(
		private SourceLocator $locator,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private string $cacheKey,
	)
	{
		$variableCacheKey = $this->getVariableCacheKey();
		$this->cached = $this->cache->load($this->cacheKey, $variableCacheKey) ?? [];
	}


	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
	{
		$key = $identifier->getName();

		$this->cached['identifier'] ??= [];
		if (!array_key_exists($key, $this->cached['identifier'])) {
			$this->cached['identifier'][$key] = $this->locator->locateIdentifier($reflector, $identifier);
			$this->storeCache();
		}

		return $this->cached['identifier'][$key];
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		$key = $identifierType->getName();

		$this->cached['identifiersByType'] ??= [];
		if (!array_key_exists($key, $this->cached['identifiersByType'])) {
			$this->cached['identifiersByType'][$key] = $this->locator->locateIdentifiersByType($reflector, $identifierType);
			$this->storeCache();
		}

		return $this->cached['identifiersByType'][$key];
	}

	private function getVariableCacheKey(): string
	{
		return sprintf('v1-%s-%s', ComposerHelper::getBetterReflectionVersion(), $this->phpVersion->getVersionString());
	}

	private function storeCache(): void
	{
		$variableCacheKey = $this->getVariableCacheKey();
		$this->cache->save($this->cacheKey, $variableCacheKey, $this->cached);
	}
}
