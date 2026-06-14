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
use PHPStan\Internal\ComposerHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_values;
use function current;
use function hash_file;
use function sprintf;
use function strtolower;

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
		private array $classToFile,
		private array $functionToFiles,
		private array $constantToFile,
	)
	{
	}

	/**
	 * @return array{non-empty-string, string}
	 */
	private function getCacheKeys(string $file, Identifier $identifier): array
	{
		$fileHash = hash_file('sha256', $file);
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
			foreach ($files as $file) {
				$fetchedClassNodes = $this->fileNodesFetcher->fetchNodes($file)->getClassNodes();

				if (!array_key_exists($identifierName, $fetchedClassNodes)) {
					return null;
				}

				/** @var FetchedNode<Node\Stmt\ClassLike> $fetchedClassNode */
				$fetchedClassNode = current($fetchedClassNodes[$identifierName]);
			}

			if ($fetchedClassNode === null) { // @phpstan-ignore identical.alwaysFalse
				return null;
			}

			[$reflectionCacheKey, $variableCacheKey] = $this->getCacheKeys($file, $identifier); // @phpstan-ignore variable.undefined
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
		if (!array_key_exists($className, $this->classToFile)) {
			return null;
		}

		return $this->classToFile[$className];
	}

	private function findFileByConstant(string $constantName): ?string
	{
		if (!array_key_exists($constantName, $this->constantToFile)) {
			return null;
		}

		return $this->constantToFile[$constantName];
	}

	/**
	 * @return string[]
	 */
	private function findFilesByFunction(string $functionName): array
	{
		if (!array_key_exists($functionName, $this->functionToFiles)) {
			return [];
		}

		return $this->functionToFiles[$functionName];
	}

	/**
	 * @return list<Reflection>
	 */
	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
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
