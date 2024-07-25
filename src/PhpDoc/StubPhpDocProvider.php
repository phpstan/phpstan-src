<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Parser\Parser;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use function array_key_exists;
use function array_map;
use function is_string;

final class StubPhpDocProvider
{

	/** @var array<string, ResolvedPhpDocBlock|null> */
	private array $classMap = [];

	/** @var array<string, array<string, ResolvedPhpDocBlock|null>> */
	private array $propertyMap = [];

	/** @var array<string, array<string, ResolvedPhpDocBlock|null>> */
	private array $constantMap = [];

	/** @var array<string, array<string, null>> */
	private array $methodMap = [];

	/** @var array<string, ResolvedPhpDocBlock|null> */
	private array $functionMap = [];

	private bool $initialized = false;

	private bool $initializing = false;

	/** @var array<string, array{string, string}> */
	private array $knownClassesDocComments = [];

	/** @var array<string, array{string, string}> */
	private array $knownFunctionsDocComments = [];

	/** @var array<string, array<string, array{string, string}>> */
	private array $knownPropertiesDocComments = [];

	/** @var array<string, array<string, array{string, string}>> */
	private array $knownConstantsDocComments = [];

	/** @var array<string, array<string, array{string, string}>> */
	private array $knownMethodsDocComments = [];

	/** @var array<string, array<string, array<string>>> */
	private array $knownMethodsParameterNames = [];

	/** @var array<string, array<string>> */
	private array $knownFunctionParameterNames = [];

	public function __construct(
		private Parser $parser,
		private FileTypeMapper $fileTypeMapper,
		private StubFilesProvider $stubFilesProvider,
	)
	{
	}

	public function findClassPhpDoc(string $className): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}

		if (array_key_exists($className, $this->classMap)) {
			return $this->classMap[$className];
		}

		if (array_key_exists($className, $this->knownClassesDocComments)) {
			[$file, $docComment] = $this->knownClassesDocComments[$className];
			$this->classMap[$className] = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				$className,
				null,
				null,
				$docComment,
			);

			return $this->classMap[$className];
		}

		return null;
	}

	public function findPropertyPhpDoc(string $className, string $propertyName): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}

		if (array_key_exists($propertyName, $this->propertyMap[$className])) {
			return $this->propertyMap[$className][$propertyName];
		}

		if (array_key_exists($propertyName, $this->knownPropertiesDocComments[$className])) {
			[$file, $docComment] = $this->knownPropertiesDocComments[$className][$propertyName];
			$this->propertyMap[$className][$propertyName] = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				$className,
				null,
				null,
				$docComment,
			);

			return $this->propertyMap[$className][$propertyName];
		}

		return null;
	}

	public function findClassConstantPhpDoc(string $className, string $constantName): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}

		if (array_key_exists($constantName, $this->constantMap[$className])) {
			return $this->constantMap[$className][$constantName];
		}

		if (array_key_exists($constantName, $this->knownConstantsDocComments[$className])) {
			[$file, $docComment] = $this->knownConstantsDocComments[$className][$constantName];
			$this->constantMap[$className][$constantName] = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				$className,
				null,
				null,
				$docComment,
			);

			return $this->constantMap[$className][$constantName];
		}

		return null;
	}

	/**
	 * @param array<int, string> $positionalParameterNames
	 */
	public function findMethodPhpDoc(
		string $className,
		string $implementingClassName,
		string $methodName,
		array $positionalParameterNames,
	): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownClass($className)) {
			return null;
		}

		if (array_key_exists($methodName, $this->methodMap[$className])) {
			return $this->methodMap[$className][$methodName];
		}

		if (array_key_exists($methodName, $this->knownMethodsDocComments[$className])) {
			[$file, $docComment] = $this->knownMethodsDocComments[$className][$methodName];
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				$className,
				null,
				$methodName,
				$docComment,
			);

			if (!isset($this->knownMethodsParameterNames[$className][$methodName])) {
				throw new ShouldNotHappenException();
			}

			if ($className !== $implementingClassName && $resolvedPhpDoc->getNullableNameScope() !== null) {
				$resolvedPhpDoc = $resolvedPhpDoc->withNameScope(
					$resolvedPhpDoc->getNullableNameScope()->withClassName($implementingClassName),
				);
			}

			$methodParameterNames = $this->knownMethodsParameterNames[$className][$methodName];
			$parameterNameMapping = [];
			foreach ($positionalParameterNames as $i => $parameterName) {
				if (!array_key_exists($i, $methodParameterNames)) {
					continue;
				}
				$parameterNameMapping[$methodParameterNames[$i]] = $parameterName;
			}

			return $resolvedPhpDoc->changeParameterNamesByMapping($parameterNameMapping);
		}

		return null;
	}

	/**
	 * @param array<int, string> $positionalParameterNames
	 * @throws ShouldNotHappenException
	 */
	public function findFunctionPhpDoc(string $functionName, array $positionalParameterNames): ?ResolvedPhpDocBlock
	{
		if (!$this->isKnownFunction($functionName)) {
			return null;
		}

		if (array_key_exists($functionName, $this->functionMap)) {
			return $this->functionMap[$functionName];
		}

		if (array_key_exists($functionName, $this->knownFunctionsDocComments)) {
			[$file, $docComment] = $this->knownFunctionsDocComments[$functionName];
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				null,
				null,
				$functionName,
				$docComment,
			);

			if (!isset($this->knownFunctionParameterNames[$functionName])) {
				throw new ShouldNotHappenException();
			}

			$functionParameterNames = $this->knownFunctionParameterNames[$functionName];
			$parameterNameMapping = [];
			foreach ($positionalParameterNames as $i => $parameterName) {
				if (!array_key_exists($i, $functionParameterNames)) {
					continue;
				}
				$parameterNameMapping[$functionParameterNames[$i]] = $parameterName;
			}

			$this->functionMap[$functionName] = $resolvedPhpDoc->changeParameterNamesByMapping($parameterNameMapping);

			return $this->functionMap[$functionName];
		}

		return null;
	}

	public function isKnownClass(string $className): bool
	{
		$this->initializeKnownElements();

		if (array_key_exists($className, $this->classMap)) {
			return true;
		}

		return array_key_exists($className, $this->knownClassesDocComments);
	}

	private function isKnownFunction(string $functionName): bool
	{
		$this->initializeKnownElements();

		if (array_key_exists($functionName, $this->functionMap)) {
			return true;
		}

		return array_key_exists($functionName, $this->knownFunctionsDocComments);
	}

	private function initializeKnownElements(): void
	{
		if ($this->initializing) {
			throw new ShouldNotHappenException();
		}
		if ($this->initialized) {
			return;
		}

		$this->initializing = true;

		try {
			foreach ($this->stubFilesProvider->getStubFiles() as $stubFile) {
				$nodes = $this->parser->parseFile($stubFile);
				foreach ($nodes as $node) {
					$this->initializeKnownElementNode($stubFile, $node);
				}
			}
		} finally {
			$this->initializing = false;
			$this->initialized = true;
		}
	}

	private function initializeKnownElementNode(string $stubFile, Node $node): void
	{
		if ($node instanceof Node\Stmt\Namespace_) {
			foreach ($node->stmts as $stmt) {
				$this->initializeKnownElementNode($stubFile, $stmt);
			}
			return;
		}

		if ($node instanceof Node\Stmt\Function_) {
			$functionName = (string) $node->namespacedName;
			$docComment = $node->getDocComment();
			if ($docComment === null) {
				$this->functionMap[$functionName] = null;
				return;
			}
			$this->knownFunctionParameterNames[$functionName] = array_map(static function (Node\Param $param): string {
				if (!$param->var instanceof Variable || !is_string($param->var->name)) {
					throw new ShouldNotHappenException();
				}

				return $param->var->name;
			}, $node->getParams());

			$this->knownFunctionsDocComments[$functionName] = [$stubFile, $docComment->getText()];
			return;
		}

		if (!$node instanceof Class_ && !$node instanceof Interface_ && !$node instanceof Trait_ && !$node instanceof Node\Stmt\Enum_) {
			return;
		}

		if (!isset($node->namespacedName)) {
			return;
		}

		$className = (string) $node->namespacedName;
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			$this->classMap[$className] = null;
		} else {
			$this->knownClassesDocComments[$className] = [$stubFile, $docComment->getText()];
		}

		$this->methodMap[$className] = [];
		$this->propertyMap[$className] = [];
		$this->constantMap[$className] = [];
		$this->knownPropertiesDocComments[$className] = [];
		$this->knownConstantsDocComments[$className] = [];
		$this->knownMethodsDocComments[$className] = [];

		foreach ($node->stmts as $stmt) {
			$docComment = $stmt->getDocComment();
			if ($stmt instanceof Node\Stmt\Property) {
				foreach ($stmt->props as $property) {
					if ($docComment === null) {
						$this->propertyMap[$className][$property->name->toString()] = null;
						continue;
					}
					$this->knownPropertiesDocComments[$className][$property->name->toString()] = [$stubFile, $docComment->getText()];
				}
			} elseif ($stmt instanceof Node\Stmt\ClassConst) {
				foreach ($stmt->consts as $const) {
					if ($docComment === null) {
						$this->constantMap[$className][$const->name->toString()] = null;
						continue;
					}
					$this->knownConstantsDocComments[$className][$const->name->toString()] = [$stubFile, $docComment->getText()];
				}
			} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
				if ($docComment === null) {
					$this->methodMap[$className][$stmt->name->toString()] = null;
					continue;
				}

				$methodName = $stmt->name->toString();
				$this->knownMethodsDocComments[$className][$methodName] = [$stubFile, $docComment->getText()];
				$this->knownMethodsParameterNames[$className][$methodName] = array_map(static function (Node\Param $param): string {
					if (!$param->var instanceof Variable || !is_string($param->var->name)) {
						throw new ShouldNotHappenException();
					}

					return $param->var->name;
				}, $stmt->getParams());
			}
		}
	}

}
