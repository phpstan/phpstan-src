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
use PHPStan\Cache\Cache;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileContentHasher;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_values;
use function current;
use function is_array;
use function sprintf;
use function strtolower;

/**
 * @phpstan-import-type ClassLikeNode from FetchedNodesResult
 */
final class OptimizedDirectorySourceLocator implements SourceLocator
{

	/**
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
		private bool $awaitingScan = false,
	)
	{
	}

	/**
	 * Fills in the symbol maps of a locator created ahead of the scan.
	 *
	 * The factory hands these out before the scan that produces their contents
	 * has run, so that one scan can cover every directory at once
	 * (see OptimizedDirectorySourceLocatorBatch). Nothing may look a symbol up
	 * in between - the maps are empty, so a lookup would quietly answer "not
	 * found" - which is what the flag guards.
	 *
	 * @param array<string, string> $classToFile
	 * @param array<string, array<int, string>> $functionToFiles
	 * @param array<string, string> $constantToFile
	 * @internal
	 */
	public function fillScanned(array $classToFile, array $functionToFiles, array $constantToFile): void
	{
		$this->classToFile = $classToFile;
		$this->functionToFiles = $functionToFiles;
		$this->constantToFile = $constantToFile;
		$this->awaitingScan = false;
	}

	/**
	 * The lowercase class names, the lowercase function names and the normalized constant names
	 * it declares - see OptimizedDirectorySourceLocatorGroup.
	 *
	 * @return array{list<string>, list<string>, list<string>}
	 */
	public function getDeclaredSymbolNames(): array
	{
		if ($this->awaitingScan) {
			throw new ShouldNotHappenException('Symbols were looked up in a directory whose batch has not been scanned yet.');
		}

		return [array_keys($this->classToFile), array_keys($this->functionToFiles), array_keys($this->constantToFile)];
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
		$variableCacheKey = sprintf('v3-%s-%s-%s', ComposerHelper::getBetterReflectionVersion(), $this->phpVersion->getVersionString(), $fileHash);

		return [$reflectionCacheKey, $variableCacheKey];
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($this->awaitingScan) {
			throw new ShouldNotHappenException('Symbols were looked up in a directory whose batch has not been scanned yet.');
		}

		if ($identifier->isClass()) {
			$identifierName = strtolower($identifier->getName());
			$fileByClass = $this->findFileByClass($identifierName);
			if ($fileByClass === null) {
				return null;
			}
			$files = [$fileByClass];
		} elseif ($identifier->isFunction()) {
			$identifierName = strtolower($identifier->getName());
			$files = $this->findFilesByFunction($identifierName);
		} elseif ($identifier->isConstant()) {
			$identifierName = ConstantNameHelper::normalize($identifier->getName());
			$fileByConstant = $this->findFileByConstant($identifierName);

			if ($fileByConstant === null) {
				return null;
			}

			$files = [$fileByConstant];
		} else {
			return null;
		}

		foreach ($files as $oneFile) {
			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($oneFile, $identifier);
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

				/** @var FetchedNode<ClassLikeNode> $fetchedClassNode */
				$fetchedClassNode = current($fetchedClassNodes[$identifierName]);
				$fetchedFile = $file;
			}

			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($fetchedFile, $identifier);
			$classReflection = $this->nodeToReflection($reflector, $fetchedClassNode);
			$this->cache->save($reflectionCacheKey, $variableCacheKey, $classReflection->exportToCache());

			return $classReflection;
		} elseif ($identifier->isFunction()) {
			$fetchedFunctionNode = null;
			$fetchedFile = null;
			foreach ($files as $file) {
				if (!$this->declares($file, $identifier, $identifierName)) {
					continue;
				}

				$fetchedFunctionNodes = $this->fileNodesFetcher->fetchNodes($file)->getFunctionNodes();

				if (!array_key_exists($identifierName, $fetchedFunctionNodes)) {
					continue;
				}

				/** @var FetchedNode<Node\Stmt\Function_> $fetchedFunctionNode */
				$fetchedFunctionNode = current($fetchedFunctionNodes[$identifierName]);
				$fetchedFile = $file;
			}

			if ($fetchedFunctionNode === null || $fetchedFile === null) {
				return null;
			}

			// the file declaring the function, not the last one searched - the symbol scan also lists
			// the files with a method of the same name, and the reflection keyed to one of them would
			// stay in the cache after the declaring file changes
			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($fetchedFile, $identifier);
			$functionReflection = $this->nodeToReflection($reflector, $fetchedFunctionNode);
			$this->cache->save($reflectionCacheKey, $variableCacheKey, $functionReflection->exportToCache());

			return $functionReflection;
		} elseif ($identifier->isConstant()) {
			$fetchedConstantNode = null;
			foreach ($files as $file) {
				if (!$this->declares($file, $identifier, $identifierName)) {
					return null;
				}

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
	 * @param FetchedNode<ClassLikeNode>|FetchedNode<Node\Stmt\Function_>|FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedNode
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

	/**
	 * Whether the file declares the function or the constant, remembered for its contents.
	 *
	 * The symbol scan lists more files than declare them - a method looks like a function to it,
	 * and define() may be called anywhere - so a function or a constant that does not exist would
	 * otherwise parse those files on each lookup, such as when the result cache checks on every
	 * run that it is still missing.
	 */
	private function declares(string $file, Identifier $identifier, string $identifierName): bool
	{
		[, $variableCacheKey] = $this->getCacheKeys($file, $identifier);
		$cacheKey = sprintf('odsl-declared-%s', $file);
		$declared = $this->cache->load($cacheKey, $variableCacheKey);
		if (!is_array($declared)) {
			$fetchedNodes = $this->fileNodesFetcher->fetchNodes($file);
			$declared = [
				'functions' => array_fill_keys(array_keys($fetchedNodes->getFunctionNodes()), true),
				'constants' => array_fill_keys(array_keys($fetchedNodes->getConstantNodes()), true),
			];
			$this->cache->save($cacheKey, $variableCacheKey, $declared);
		}

		return isset($declared[$identifier->isFunction() ? 'functions' : 'constants'][$identifierName]);
	}

	private function findFileByClass(string $className): ?string
	{
		return $this->classToFile[$className] ?? null;
	}

	private function findFileByConstant(string $constantName): ?string
	{
		return $this->constantToFile[$constantName] ?? null;
	}

	/**
	 * @return string[]
	 */
	private function findFilesByFunction(string $functionName): array
	{
		return $this->functionToFiles[$functionName] ?? [];
	}

	/**
	 * @return list<Reflection>
	 */
	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		if ($this->awaitingScan) {
			throw new ShouldNotHappenException('Symbols were looked up in a directory whose batch has not been scanned yet.');
		}

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
