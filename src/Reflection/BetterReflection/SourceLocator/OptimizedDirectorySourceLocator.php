<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_values;
use function current;
use function php_strip_whitespace;
use function strtolower;

class OptimizedDirectorySourceLocator implements SourceLocator
{

	/** @var array<string, string|null> */
	private array $classToFile = [];

	/** @var array<string, string|null> */
	private array $constantToFile = [];

	/** @var array<string, string|null> */
	private array $functionToFiles = [];

	private NodeTraverser $nodeTraverser;

	/**
	 * @param string[] $files
	 */
	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private PhpVersion $phpVersion,
		private array $files,
		private Parser $phpParser,
		private CachingVisitor $cachingVisitor,
	)
	{
		$this->nodeTraverser = new NodeTraverser();
		$this->nodeTraverser->addVisitor(new NameResolver());
		$this->nodeTraverser->addVisitor($cachingVisitor);
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = strtolower($identifier->getName());

			if (!array_key_exists($className, $this->classToFile)) {
				$found = $this->parse(fn () => array_key_exists($className, $this->classToFile));

				if (!$found) {
					// Don't try to search twice
					$this->classToFile[$className] = null;
				}
			}

			$file = $this->classToFile[$className];

			if ($file === null) {
				return null;
			}

			$fetchedClassNodes = $this->fileNodesFetcher->fetchNodes($file)->getClassNodes();

			/** @var FetchedNode<Node\Stmt\ClassLike> $fetchedClassNode */
			$fetchedClassNode = current($fetchedClassNodes[$className]);

			if ($fetchedClassNode->getNode() instanceof Node\Stmt\Enum_ && !$this->phpVersion->supportsEnums()) {
				return null;
			}

			return $this->nodeToReflection($reflector, $fetchedClassNode);
		}

		if ($identifier->isFunction()) {
			$functionName = strtolower($identifier->getName());

			if (!array_key_exists($functionName, $this->functionToFiles)) {
				$found = $this->parse(fn () => array_key_exists($functionName, $this->functionToFiles));

				if (!$found) {
					// Don't try to search twice
					$this->functionToFiles[$functionName] = null;
				}
			}

			$file = $this->functionToFiles[$functionName];

			if ($file === null) {
				return null;
			}

			$fetchedFunctionNodes = $this->fileNodesFetcher->fetchNodes($file)->getFunctionNodes();

			/** @var FetchedNode<Node\Stmt\Function_> $fetchedFunctionNode */
			$fetchedFunctionNode = current($fetchedFunctionNodes[$functionName]);

			return $this->nodeToReflection($reflector, $fetchedFunctionNode);
		}

		if ($identifier->isConstant()) {
			$constantName = ConstantNameHelper::normalize($identifier->getName());

			if (!array_key_exists($constantName, $this->constantToFile)) {
				$found = $this->parse(fn () => array_key_exists($constantName, $this->constantToFile));

				if (!$found) {
					// Don't try to search twice
					$this->constantToFile[$constantName] = null;
				}
			}

			$file = $this->constantToFile[$constantName];

			if ($file === null) {
				return null;
			}

			$fetchedConstantNodes = $this->fileNodesFetcher->fetchNodes($file)->getConstantNodes();

			/** @var FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedConstantNode */
			$fetchedConstantNode = current($fetchedConstantNodes[$constantName]);

			return $this->nodeToReflection(
				$reflector,
				$fetchedConstantNode,
				$this->findConstantPositionInConstNode($fetchedConstantNode->getNode(), $constantName),
			);
		}

		return null;
	}

	/**
	 * @return list<Reflection>
	 */
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		$this->parse();

		$reflections = [];
		if ($identifierType->isClass()) {
			foreach (array_filter($this->classToFile) as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNodes) {
					foreach ($fetchedClassNodes as $fetchedClassNode) {
						if (!$this->phpVersion->supportsEnums() && $fetchedClassNode->getNode() instanceof Node\Stmt\Enum_) {
							continue;
						}

						$reflections[$identifierName] = $this->nodeToReflection($reflector, $fetchedClassNode);
					}
				}
			}
		} elseif ($identifierType->isFunction()) {
			foreach (array_filter($this->functionToFiles) as $file) {
				$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
				foreach ($fetchedNodesResult->getFunctionNodes() as $identifierName => $fetchedFunctionNodes) {
					foreach ($fetchedFunctionNodes as $fetchedFunctionNode) {
						$reflections[$identifierName] = $this->nodeToReflection($reflector, $fetchedFunctionNode);
					}
				}
			}
		} elseif ($identifierType->isConstant()) {
			foreach (array_filter($this->constantToFile) as $file) {
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

	/**
	 * @param (callable(): bool) $isTypeFound
	 */
	private function parse(?callable $isTypeFound = null): bool
	{
		foreach ($this->files as $filesNo => $file) {
			$fetchNodesResult = $this->parseFile($file);

			foreach (array_keys($fetchNodesResult->getClassNodes()) as $className) {
				$this->classToFile[$className] = $file;
			}

			foreach (array_keys($fetchNodesResult->getFunctionNodes()) as $functionName) {
				$this->functionToFiles[$functionName] = $file;
			}

			foreach (array_keys($fetchNodesResult->getConstantNodes()) as $constantName) {
				$this->constantToFile[$constantName] = $file;
			}

			unset($this->files[$filesNo]);

			if ($isTypeFound !== null && $isTypeFound()) {
				return true;
			}
		}

		return false;
	}

	private function parseFile(string $file): FetchedNodesResult
	{
		$contents = @php_strip_whitespace($file);

		try {
			/** @var Node[] $ast */
			$ast = $this->phpParser->parse($contents);
		} catch (Throwable) {
			return new FetchedNodesResult([], [], []);
		}

		$this->cachingVisitor->prepare($file, $contents);
		$this->nodeTraverser->traverse($ast);

		$result = new FetchedNodesResult(
			$this->cachingVisitor->getClassNodes(),
			$this->cachingVisitor->getFunctionNodes(),
			$this->cachingVisitor->getConstantNodes(),
		);

		$this->cachingVisitor->reset();

		return $result;
	}

	/**
	 * @param FetchedNode<Node\Stmt\ClassLike>|FetchedNode<Node\Stmt\Function_>|FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall> $fetchedNode
	 */
	private function nodeToReflection(Reflector $reflector, FetchedNode $fetchedNode, ?int $positionInNode = null): Reflection
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
