<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_values;
use function current;
use function strtolower;

class OptimizedDirectorySourceLocator implements SourceLocator
{

	/**
	 * @param array<string, string> $classToFile
	 * @param array<string, array<int, string>> $functionToFiles
	 * @param array<string, string> $constantToFile
	 */
	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private array $classToFile,
		private array $functionToFiles,
		private array $constantToFile,
	)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isClass()) {
			$className = strtolower($identifier->getName());
			$file = $this->findFileByClass($className);
			if ($file === null) {
				return null;
			}

			$fetchedClassNodes = $this->fileNodesFetcher->fetchNodes($file)->getClassNodes();

			if (!array_key_exists($className, $fetchedClassNodes)) {
				return null;
			}

			/** @var FetchedNode<Node\Stmt\ClassLike> $fetchedClassNode */
			$fetchedClassNode = current($fetchedClassNodes[$className]);

			return $this->nodeToReflection($reflector, $fetchedClassNode);
		}

		if ($identifier->isFunction()) {
			$functionName = strtolower($identifier->getName());
			$files = $this->findFilesByFunction($functionName);

			$fetchedFunctionNode = null;
			foreach ($files as $file) {
				$fetchedFunctionNodes = $this->fileNodesFetcher->fetchNodes($file)->getFunctionNodes();

				if (!array_key_exists($functionName, $fetchedFunctionNodes)) {
					continue;
				}

				/** @var FetchedNode<Node\Stmt\Function_> $fetchedFunctionNode */
				$fetchedFunctionNode = current($fetchedFunctionNodes[$functionName]);
			}

			if ($fetchedFunctionNode === null) {
				return null;
			}

			return $this->nodeToReflection($reflector, $fetchedFunctionNode);
		}

		if ($identifier->isConstant()) {
			$constantName = ConstantNameHelper::normalize($identifier->getName());
			$file = $this->findFileByConstant($constantName);

			if ($file === null) {
				return null;
			}

			$fetchedConstantNodes = $this->fileNodesFetcher->fetchNodes($file)->getConstantNodes();

			if (!array_key_exists($constantName, $fetchedConstantNodes)) {
				return null;
			}

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
