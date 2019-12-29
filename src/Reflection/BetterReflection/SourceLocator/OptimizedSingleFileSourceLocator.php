<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class OptimizedSingleFileSourceLocator implements SourceLocator
{

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher */
	private $fileNodesFetcher;

	/** @var string */
	private $fileName;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult|null */
	private $fetchedNodesResult;

	public function __construct(
		FileNodesFetcher $fileNodesFetcher,
		string $fileName
	)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileName = $fileName;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($this->fetchedNodesResult === null) {
			$this->fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
		}
		$nodeToReflection = new NodeToReflection();
		if ($identifier->isClass()) {
			$classNodes = $this->fetchedNodesResult->getClassNodes();
			if (!array_key_exists($identifier->getName(), $classNodes)) {
				return null;
			}

			$classReflection = $nodeToReflection->__invoke(
				$reflector,
				$classNodes[$identifier->getName()]->getNode(),
				$this->fetchedNodesResult->getLocatedSource(),
				$classNodes[$identifier->getName()]->getNamespace()
			);
			if (!$classReflection instanceof ReflectionClass) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			return $classReflection;
		}

		if ($identifier->isFunction()) {
			$functionNodes = $this->fetchedNodesResult->getFunctionNodes();
			if (!array_key_exists($identifier->getName(), $functionNodes)) {
				return null;
			}

			$functionReflection = $nodeToReflection->__invoke(
				$reflector,
				$functionNodes[$identifier->getName()]->getNode(),
				$this->fetchedNodesResult->getLocatedSource(),
				$functionNodes[$identifier->getName()]->getNamespace()
			);
			if (!$functionReflection instanceof ReflectionFunction) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			return $functionReflection;
		}

		if ($identifier->isConstant()) {
			$constantNodes = $this->fetchedNodesResult->getConstantNodes();
			if (!array_key_exists($identifier->getName(), $constantNodes)) {
				return null;
			}

			$constantReflection = $nodeToReflection->__invoke(
				$reflector,
				$constantNodes[$identifier->getName()]->getNode(),
				$this->fetchedNodesResult->getLocatedSource(),
				$constantNodes[$identifier->getName()]->getNamespace()
			);
			if (!$constantReflection instanceof ReflectionConstant) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			return $constantReflection;
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return []; // todo
	}

}
