<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Php8StubsMap;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ParserNodeTypeToPHPStanType;

class Php8SignatureMapProvider implements SignatureMapProvider
{

	private const DIRECTORY = __DIR__ . '/../../../vendor/phpstan/php-8-stubs';

	private FunctionSignatureMapProvider $functionSignatureMapProvider;

	private FileNodesFetcher $fileNodesFetcher;

	/** @var array<string, array<string, ClassMethod>> */
	private array $methodNodes = [];

	public function __construct(
		FunctionSignatureMapProvider $functionSignatureMapProvider,
		FileNodesFetcher $fileNodesFetcher
	)
	{
		$this->functionSignatureMapProvider = $functionSignatureMapProvider;
		$this->fileNodesFetcher = $fileNodesFetcher;
	}

	public function hasMethodSignature(string $className, string $methodName, int $variant = 0): bool
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, Php8StubsMap::CLASSES)) {
			return $this->functionSignatureMapProvider->hasMethodSignature($className, $methodName, $variant);
		}

		if ($variant > 0) {
			return $this->functionSignatureMapProvider->hasMethodSignature($className, $methodName, $variant);
		}

		return $this->findMethodNode($className, $methodName) !== null;
	}

	private function findMethodNode(string $className, string $methodName): ?ClassMethod
	{
		$lowerClassName = strtolower($className);
		$lowerMethodName = strtolower($methodName);
		if (isset($this->methodNodes[$lowerClassName][$lowerMethodName])) {
			return $this->methodNodes[$lowerClassName][$lowerMethodName];
		}

		$stubFile = self::DIRECTORY . '/' . Php8StubsMap::CLASSES[$lowerClassName];
		$nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
		$classes = $nodes->getClassNodes();
		if (count($classes) !== 1) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Class %s stub not found in %s.', $className, $stubFile));
		}

		$class = $classes[$lowerClassName];
		if (count($class) !== 1) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Class %s stub not found in %s.', $className, $stubFile));
		}

		foreach ($class[0]->getNode()->stmts as $stmt) {
			if (!$stmt instanceof ClassMethod) {
				continue;
			}

			if ($stmt->name->toLowerString() === $lowerMethodName) {
				$this->methodNodes[$lowerClassName][$lowerMethodName] = $stmt;

				return $stmt;
			}
		}

		return null;
	}

	public function hasFunctionSignature(string $name, int $variant = 0): bool
	{
		$lowerName = strtolower($name);
		if (!array_key_exists($lowerName, Php8StubsMap::FUNCTIONS)) {
			return $this->functionSignatureMapProvider->hasFunctionSignature($name, $variant);
		}

		if ($variant > 0) {
			return $this->functionSignatureMapProvider->hasFunctionSignature($name, $variant);
		}

		return true;
	}

	public function getMethodSignature(string $className, string $methodName, int $variant = 0): FunctionSignature
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, Php8StubsMap::CLASSES)) {
			return $this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $variant);
		}

		if ($variant > 0) {
			return $this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $variant);
		}

		$methodNode = $this->findMethodNode($className, $methodName);
		if ($methodNode === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $this->getSignature($methodNode);
	}

	public function getFunctionSignature(string $functionName, ?string $className, int $variant = 0): FunctionSignature
	{
		$lowerName = strtolower($functionName);
		if (!array_key_exists($lowerName, Php8StubsMap::FUNCTIONS)) {
			return $this->functionSignatureMapProvider->getFunctionSignature($functionName, $className, $variant);
		}

		if ($variant > 0) {
			return $this->functionSignatureMapProvider->getFunctionSignature($functionName, $className, $variant);
		}

		$stubFile = self::DIRECTORY . '/' . Php8StubsMap::FUNCTIONS[$lowerName];
		$nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
		$functions = $nodes->getFunctionNodes();
		if (count($functions) !== 1) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
		}

		return $this->getSignature($functions[$lowerName]->getNode());
	}

	public function hasMethodMetadata(string $className, string $methodName): bool
	{
		return $this->functionSignatureMapProvider->hasMethodMetadata($className, $methodName);
	}

	public function hasFunctionMetadata(string $name): bool
	{
		return $this->functionSignatureMapProvider->hasFunctionMetadata($name);
	}

	/**
	 * @param string $className
	 * @param string $methodName
	 * @return array{hasSideEffects: bool}
	 */
	public function getMethodMetadata(string $className, string $methodName): array
	{
		return $this->functionSignatureMapProvider->getMethodMetadata($className, $methodName);
	}

	/**
	 * @param string $functionName
	 * @return array{hasSideEffects: bool}
	 */
	public function getFunctionMetadata(string $functionName): array
	{
		return $this->functionSignatureMapProvider->getFunctionMetadata($functionName);
	}

	private function getSignature(FunctionLike $function): FunctionSignature
	{
		$parameters = [];
		$variadic = false;
		foreach ($function->getParams() as $param) {
			$name = $param->var;
			if (!$name instanceof Variable || !is_string($name->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$parameters[] = new ParameterSignature(
				$name->name,
				$param->default !== null || $param->variadic,
				ParserNodeTypeToPHPStanType::resolve($param->type, null),
				$param->byRef ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(),
				$param->variadic
			);

			$variadic = $variadic || $param->variadic;
		}

		return new FunctionSignature(
			$parameters,
			ParserNodeTypeToPHPStanType::resolve($function->getReturnType(), null),
			$variadic
		);
	}

}
