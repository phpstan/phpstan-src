<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\File\FileFinder;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function array_key_exists;

class OptimizedDirectorySourceLocator implements SourceLocator
{

	private \PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher $fileNodesFetcher;

	private \PHPStan\File\FileFinder $fileFinder;

	private string $directory;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\ClassLike>> */
	private array $classNodes = [];

	/** @var array<string, \Roave\BetterReflection\SourceLocator\Located\LocatedSource> */
	private array $locatedSourcesByFile;

	private bool $initialized = false;

	public function __construct(
		FileNodesFetcher $fileNodesFetcher,
		FileFinder $fileFinder,
		string $directory
	)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileFinder = $fileFinder;
		$this->directory = $directory;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$this->parseFiles();

		$nodeToReflection = new NodeToReflection();
		if ($identifier->isClass()) {
			if (!array_key_exists($identifier->getName(), $this->classNodes)) {
				return null;
			}

			$fetchedNode = $this->classNodes[$identifier->getName()];
			$classReflection = $nodeToReflection->__invoke(
				$reflector,
				$fetchedNode->getNode(),
				$this->locatedSourcesByFile[$fetchedNode->getFileName()],
				$fetchedNode->getNamespace()
			);

			if (!$classReflection instanceof ReflectionClass) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			return $classReflection;
		}

		return null;
	}

	private function parseFiles(): void
	{
		if ($this->initialized) {
			return;
		}

		$fileFinderResult = $this->fileFinder->findFiles([$this->directory]);
		foreach ($fileFinderResult->getFiles() as $file) {
			$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($file);
			$locatedSource = $fetchedNodesResult->getLocatedSource();
			$this->locatedSourcesByFile[$file] = $locatedSource;
			foreach ($fetchedNodesResult->getClassNodes() as $identifierName => $fetchedClassNode) {
				$this->classNodes[$identifierName] = $fetchedClassNode;
			}
		}

		$this->initialized = true;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return []; // todo
	}

}
