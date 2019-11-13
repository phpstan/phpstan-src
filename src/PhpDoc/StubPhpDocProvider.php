<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Parser\Parser;
use PHPStan\Type\FileTypeMapper;
use function array_key_exists;

class StubPhpDocProvider
{

	/** @var \PHPStan\Parser\Parser */
	private $parser;

	/** @var \PHPStan\Type\FileTypeMapper */
	private $fileTypeMapper;

	/** @var \PHPStan\PhpDoc\TypeNodeResolver */
	private $typeNodeResolver;

	/** @var string[] */
	private $stubFiles;

	/** @var array<string, ResolvedPhpDocBlock>|null */
	private $classMap;

	/** @var array<string, array<string, ResolvedPhpDocBlock>>|null */
	private $propertyMap;

	/** @var array<string, array<string, ResolvedPhpDocBlock>>|null */
	private $methodMap;

	/** @var array<string, ResolvedPhpDocBlock>|null */
	private $functionMap;

	/** @var bool */
	private $initialized = false;

	/** @var bool */
	private $initializing = false;

	/** @var array<string, true>|null */
	private $knownClasses;

	/** @var array<string, true>|null */
	private $knownFunctions;

	/**
	 * @param \PHPStan\Parser\Parser $parser
	 * @param string[] $stubFiles
	 */
	public function __construct(
		Parser $parser,
		FileTypeMapper $fileTypeMapper,
		TypeNodeResolver $typeNodeResolver,
		array $stubFiles
	)
	{
		$this->parser = $parser;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->typeNodeResolver = $typeNodeResolver;
		$this->stubFiles = $stubFiles;
	}

	public function findClassPhpDoc(string $className): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}
		if (!$this->initialized) {
			$this->initialize();
		}

		if (isset($this->classMap[$className])) {
			return $this->classMap[$className];
		}

		return null;
	}

	public function findPropertyPhpDoc(string $className, string $propertyName): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}
		if (!$this->initialized) {
			$this->initialize();
		}

		if (isset($this->propertyMap[$className][$propertyName])) {
			return $this->propertyMap[$className][$propertyName];
		}

		return null;
	}

	public function findMethodPhpDoc(string $className, string $methodName): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}
		if (!$this->initialized) {
			$this->initialize();
		}

		if (isset($this->methodMap[$className][$methodName])) {
			return $this->methodMap[$className][$methodName];
		}

		return null;
	}

	public function findFunctionPhpDoc(string $functionName): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownFunction($functionName)) {
			return null;
		}
		if (!$this->initialized) {
			$this->initialize();
		}

		if (isset($this->functionMap[$functionName])) {
			return $this->functionMap[$functionName];
		}

		return null;
	}

	private function initialize(): void
	{
		if ($this->initializing) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$this->initializing = true;
		$this->typeNodeResolver->disableIterableGenericFallback();

		foreach ($this->stubFiles as $stubFile) {
			$nodes = $this->parser->parseFile($stubFile);
			$this->processNodes($stubFile, $nodes);
		}

		$this->initializing = false;
		$this->initialized = true;
		$this->typeNodeResolver->enableIterableGenericFallback();
	}

	/**
	 * @param string $stubFile
	 * @param \PhpParser\Node[] $nodes
	 */
	private function processNodes(string $stubFile, array $nodes): void
	{
		foreach ($nodes as $node) {
			$this->processNode($stubFile, $node);
		}
	}

	private function processNode(string $stubFile, Node $node): void
	{
		if ($node instanceof Node\Stmt\Namespace_) {
			foreach ($node->stmts as $stmt) {
				$this->processNode($stubFile, $stmt);
			}
			return;
		}
		if ($node instanceof Node\Stmt\Function_) {
			if ($node->getDocComment() !== null) {
				$functionName = (string) $node->namespacedName;
				$this->functionMap[$functionName] = $this->fileTypeMapper->getResolvedPhpDoc(
					$stubFile,
					null,
					null,
					$functionName,
					$node->getDocComment()->getText()
				);
			}
			return;
		}
		if (!$node instanceof Class_ && !$node instanceof Interface_ && !$node instanceof Trait_) {
			return;
		}
		if (!isset($node->namespacedName)) {
			return;
		}

		$className = (string) $node->namespacedName;
		if ($node->getDocComment() !== null) {
			$this->classMap[$className] = $this->fileTypeMapper->getResolvedPhpDoc(
				$stubFile,
				$className,
				null,
				null,
				$node->getDocComment()->getText()
			);
		}

		foreach ($node->stmts as $stmt) {
			if ($stmt->getDocComment() === null) {
				continue;
			}

			if ($stmt instanceof Node\Stmt\Property) {
				$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$stubFile,
					$className,
					null,
					null,
					$stmt->getDocComment()->getText()
				);

				foreach ($stmt->props as $property) {
					$this->propertyMap[$className][$property->name->toString()] = $resolvedPhpDoc;
				}
				continue;
			} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
				$methodName = $stmt->name->toString();
				$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$stubFile,
					$className,
					null,
					$methodName,
					$stmt->getDocComment()->getText()
				);
				$this->methodMap[$className][$methodName] = $resolvedPhpDoc;
			}
		}
	}

	private function isKnownClass(string $className): bool
	{
		if ($this->knownClasses !== null) {
			return array_key_exists($className, $this->knownClasses);
		}

		$this->initializeKnownElements();

		/** @var array<string, true> $knownClasses */
		$knownClasses = $this->knownClasses;

		return array_key_exists($className, $knownClasses);
	}

	private function isKnownFunction(string $functionName): bool
	{
		if ($this->knownFunctions !== null) {
			return array_key_exists($functionName, $this->knownFunctions);
		}

		$this->initializeKnownElements();

		/** @var array<string, true> $knownFunctions */
		$knownFunctions = $this->knownFunctions;

		return array_key_exists($functionName, $knownFunctions);
	}

	private function initializeKnownElements(): void
	{
		$this->knownClasses = [];
		$this->knownFunctions = [];

		foreach ($this->stubFiles as $stubFile) {
			$nodes = $this->parser->parseFile($stubFile);
			foreach ($nodes as $node) {
				$this->initializeKnownElementNode($node);
			}
		}
	}

	private function initializeKnownElementNode(Node $node): void
	{
		if ($node instanceof Node\Stmt\Namespace_) {
			foreach ($node->stmts as $stmt) {
				$this->initializeKnownElementNode($stmt);
			}
			return;
		}

		if ($node instanceof Node\Stmt\Function_) {
			$functionName = (string) $node->namespacedName;
			$this->knownFunctions[$functionName] = true;
			return;
		}

		if (!$node instanceof Class_ && !$node instanceof Interface_ && !$node instanceof Trait_) {
			return;
		}

		if (!isset($node->namespacedName)) {
			return;
		}

		$className = (string) $node->namespacedName;
		$this->knownClasses[$className] = true;
	}

}
