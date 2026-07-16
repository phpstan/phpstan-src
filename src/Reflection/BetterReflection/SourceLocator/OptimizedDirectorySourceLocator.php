<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PhpParser\Node;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\ArenaCache;
use PHPStan\Cache\Cache;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileContentHasher;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_values;
use function current;
use function is_array;
use function is_string;
use function sprintf;
use function strtolower;

final class OptimizedDirectorySourceLocator implements SourceLocator
{

	/** @var array<string, string|false> */
	private array $arenaClassLookups = [];

	/** @var array<string, array<int, string>|false> */
	private array $arenaFunctionLookups = [];

	/** @var array<string, string|false> */
	private array $arenaConstantLookups = [];

	private bool $hydratedFromArena = false;

	/**
	 * With $arenaKeyPrefix set, the maps start empty and names resolve lazily
	 * from the run's shared arena (published by whichever process built this
	 * directory's index first), so the worker materializes only the names it
	 * touches instead of the whole index.
	 *
	 * @param array<string, string> $classToFile
	 * @param array<string, array<int, string>> $functionToFiles
	 * @param array<string, string> $constantToFile
	 */
	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private FileContentHasher $fileContentHasher,
		private array $classToFile,
		private array $functionToFiles,
		private array $constantToFile,
		private ?string $arenaKeyPrefix = null,
	)
	{
	}

	/**
	 * @return array{non-empty-string, string}
	 */
	private function getCacheKeys(string $file, Identifier $identifier): array
	{
		$fileHash = $this->fileContentHasher->hash($file);
		if ($fileHash === false) {
			throw new CouldNotReadFileException($file);
		}

		$reflectionCacheKey = sprintf('odsl-%s-%s-%s', $file, $identifier->getType()->getName(), $identifier->getName());
		$variableCacheKey = sprintf('v2-%s-%s-%s', ComposerHelper::getBetterReflectionVersion(), $this->phpVersion->getVersionString(), $fileHash);

		return [$reflectionCacheKey, $variableCacheKey];
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$identifierName = strtolower($identifier->getName());
			$file = $this->findFileByClass($identifierName);
			if ($file === null) {
				return null;
			}
			$files = [$file];
		} elseif ($identifier->isFunction()) {
			$identifierName = strtolower($identifier->getName());
			$files = $this->findFilesByFunction($identifierName);
		} elseif ($identifier->isConstant()) {
			$identifierName = ConstantNameHelper::normalize($identifier->getName());
			$file = $this->findFileByConstant($identifierName);

			if ($file === null) {
				return null;
			}

			$files = [$file];
		} else {
			return null;
		}

		foreach ($files as $file) {
			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($file, $identifier);
			$cachedReflection = $this->cache->load($reflectionCacheKey, $variableCacheKey);
			if ($cachedReflection === null) {
				continue;
			}

			if ($identifier->isConstant()) {
				return ReflectionConstant::importFromCache($reflector, $cachedReflection);
			}
			if ($identifier->isFunction()) {
				return ReflectionFunction::importFromCache($reflector, $cachedReflection);
			}
			if ($identifier->isClass()) {
				if (array_key_exists('backingType', $cachedReflection)) {
					return ReflectionEnum::importFromCache($reflector, $cachedReflection);
				}

				return ReflectionClass::importFromCache($reflector, $cachedReflection);
			}
		}

		if ($identifier->isClass()) {
			$fetchedClassNode = null;
			$fetchedFile = null;
			foreach ($files as $file) {
				$fetchedClassNodes = $this->fileNodesFetcher->fetchNodes($file)->getClassNodes();

				if (!array_key_exists($identifierName, $fetchedClassNodes)) {
					return null;
				}

				/** @var FetchedNode<Node\Stmt\ClassLike> $fetchedClassNode */
				$fetchedClassNode = current($fetchedClassNodes[$identifierName]);
				$fetchedFile = $file;
			}

			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($fetchedFile, $identifier);
			$classReflection = $this->nodeToReflection($reflector, $fetchedClassNode);
			$this->cache->save($reflectionCacheKey, $variableCacheKey, $classReflection->exportToCache());

			return $classReflection;
		} elseif ($identifier->isFunction()) {
			$fetchedFunctionNode = null;
			foreach ($files as $file) {
				$fetchedFunctionNodes = $this->fileNodesFetcher->fetchNodes($file)->getFunctionNodes();

				if (!array_key_exists($identifierName, $fetchedFunctionNodes)) {
					continue;
				}

				/** @var FetchedNode<Node\Stmt\Function_> $fetchedFunctionNode */
				$fetchedFunctionNode = current($fetchedFunctionNodes[$identifierName]);
			}

			if ($fetchedFunctionNode === null) {
				return null;
			}

			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($file, $identifier); // @phpstan-ignore variable.undefined
			$functionReflection = $this->nodeToReflection($reflector, $fetchedFunctionNode);
			$this->cache->save($reflectionCacheKey, $variableCacheKey, $functionReflection->exportToCache());

			return $functionReflection;
		} elseif ($identifier->isConstant()) {
			$fetchedConstantNode = null;
			foreach ($files as $file) {
				$fetchedConstantNodes = $this->fileNodesFetcher->fetchNodes($file)->getConstantNodes();

				if (!array_key_exists($identifierName, $fetchedConstantNodes)) {
					return null;
				}

				/** @var FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedConstantNode */
				$fetchedConstantNode = current($fetchedConstantNodes[$identifierName]);
			}

			if ($fetchedConstantNode === null) {
				return null;
			}

			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($file, $identifier);
			$constantReflection = $this->nodeToReflection(
				$reflector,
				$fetchedConstantNode,
				$this->findConstantPositionInConstNode($fetchedConstantNode->getNode(), $identifierName),
			);
			$this->cache->save($reflectionCacheKey, $variableCacheKey, $constantReflection->exportToCache());

			return $constantReflection;
		}

		return null;
	}

	/**
	 * @param FetchedNode<Node\Stmt\ClassLike>|FetchedNode<Node\Stmt\Function_>|FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedNode
	 */
	private function nodeToReflection(Reflector $reflector, FetchedNode $fetchedNode, ?int $positionInNode = null): ReflectionClass|ReflectionConstant|ReflectionFunction
	{
		$nodeToReflection = new NodeToReflection();
		return $nodeToReflection->__invoke(
			$reflector,
			$fetchedNode->getNode(),
			$fetchedNode->getLocatedSource(),
			$fetchedNode->getNamespace(),
			$positionInNode,
		);
	}

	private function findFileByClass(string $className): ?string
	{
		if (array_key_exists($className, $this->classToFile)) {
			return $this->classToFile[$className];
		}

		if ($this->arenaKeyPrefix === null) {
			return null;
		}

		if (array_key_exists($className, $this->arenaClassLookups)) {
			$file = $this->arenaClassLookups[$className];
		} else {
			$file = ArenaCache::lookupHash($this->arenaKeyPrefix . '-classes', $className);
			if (!is_string($file)) {
				$file = false;
			}
			$this->arenaClassLookups[$className] = $file;
		}

		return $file === false ? null : $file;
	}

	private function findFileByConstant(string $constantName): ?string
	{
		if (array_key_exists($constantName, $this->constantToFile)) {
			return $this->constantToFile[$constantName];
		}

		if ($this->arenaKeyPrefix === null) {
			return null;
		}

		if (array_key_exists($constantName, $this->arenaConstantLookups)) {
			$file = $this->arenaConstantLookups[$constantName];
		} else {
			$file = ArenaCache::lookupHash($this->arenaKeyPrefix . '-constants', $constantName);
			if (!is_string($file)) {
				$file = false;
			}
			$this->arenaConstantLookups[$constantName] = $file;
		}

		return $file === false ? null : $file;
	}

	/**
	 * @return string[]
	 */
	private function findFilesByFunction(string $functionName): array
	{
		if (array_key_exists($functionName, $this->functionToFiles)) {
			return $this->functionToFiles[$functionName];
		}

		if ($this->arenaKeyPrefix === null) {
			return [];
		}

		if (array_key_exists($functionName, $this->arenaFunctionLookups)) {
			$files = $this->arenaFunctionLookups[$functionName];
		} else {
			/** @var array<int, string>|mixed $files */
			$files = ArenaCache::lookupHash($this->arenaKeyPrefix . '-functions', $functionName);
			if (!is_array($files)) {
				$files = false;
			}
			$this->arenaFunctionLookups[$functionName] = $files;
		}

		return $files === false ? [] : $files;
	}

	/**
	 * Enumeration needs the full maps: hydrates them from the arena records
	 * in their publication order, which equals the insertion order a locally
	 * built index would have. A null (a corrupt record — impossible with an
	 * intact arena, the factory gated on all three records) leaves a map
	 * empty rather than failing the run.
	 */
	private function hydrateSymbolsFromArena(): void
	{
		if ($this->arenaKeyPrefix === null || $this->hydratedFromArena) {
			return;
		}

		$this->hydratedFromArena = true;

		/** @var array<string, string>|null $classes */
		$classes = ArenaCache::lookupHashAll($this->arenaKeyPrefix . '-classes');
		if ($classes !== null) {
			$this->classToFile = $classes;
		}

		/** @var array<string, array<int, string>>|null $functions */
		$functions = ArenaCache::lookupHashAll($this->arenaKeyPrefix . '-functions');
		if ($functions !== null) {
			$this->functionToFiles = $functions;
		}

		/** @var array<string, string>|null $constants */
		$constants = ArenaCache::lookupHashAll($this->arenaKeyPrefix . '-constants');
		if ($constants === null) {
			return;
		}

		$this->constantToFile = $constants;
	}

	/**
	 * @return list<Reflection>
	 */
	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		$this->hydrateSymbolsFromArena();

		$reflections = [];
		if ($identifierType->isClass()) {
			foreach ($this->classToFile as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNodes) {
					foreach ($fetchedClassNodes as $fetchedClassNode) {
						$reflections[$identifierName] = $this->nodeToReflection($reflector, $fetchedClassNode);
					}
				}
			}
		} elseif ($identifierType->isFunction()) {
			foreach ($this->functionToFiles as $files) {
				foreach ($files as $file) {
					$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
					foreach ($fetchedNodesResult->getFunctionNodes() as $identifierName => $fetchedFunctionNodes) {
						foreach ($fetchedFunctionNodes as $fetchedFunctionNode) {
							$reflections[$identifierName] = $this->nodeToReflection($reflector, $fetchedFunctionNode);
							continue 2;
						}
					}
				}
			}
		} elseif ($identifierType->isConstant()) {
			foreach ($this->constantToFile as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getConstantNodes() as $identifierName => $fetchedConstantNodes) {
					foreach ($fetchedConstantNodes as $fetchedConstantNode) {
						$reflections[$identifierName] = $this->nodeToReflection(
							$reflector,
							$fetchedConstantNode,
							$this->findConstantPositionInConstNode($fetchedConstantNode->getNode(), $identifierName),
						);
					}
				}
			}
		}

		return array_values($reflections);
	}

	private function findConstantPositionInConstNode(Node\Stmt\Const_|Node\Expr\FuncCall $constantNode, string $constantName): ?int
	{
		if ($constantNode instanceof Node\Expr\FuncCall) {
			return null;
		}

		/** @var int $position */
		foreach ($constantNode->consts as $position => $const) {
			if ($const->namespacedName === null) {
				throw new ShouldNotHappenException();
			}

			if (ConstantNameHelper::normalize($const->namespacedName->toString()) === $constantName) {
				return $position;
			}
		}

		throw new ShouldNotHappenException();
	}

}
