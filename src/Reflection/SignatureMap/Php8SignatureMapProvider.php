<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Php8StubsMap;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;

class Php8SignatureMapProvider implements SignatureMapProvider
{

	private const DIRECTORY = __DIR__ . '/../../../vendor/phpstan/php-8-stubs';

	private FunctionSignatureMapProvider $functionSignatureMapProvider;

	private FileNodesFetcher $fileNodesFetcher;

	private FileTypeMapper $fileTypeMapper;

	/** @var array<string, array<string, array{ClassMethod, string}>> */
	private array $methodNodes = [];

	public function __construct(
		FunctionSignatureMapProvider $functionSignatureMapProvider,
		FileNodesFetcher $fileNodesFetcher,
		FileTypeMapper $fileTypeMapper
	)
	{
		$this->functionSignatureMapProvider = $functionSignatureMapProvider;
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileTypeMapper = $fileTypeMapper;
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

		if ($this->findMethodNode($className, $methodName) === null) {
			return $this->functionSignatureMapProvider->hasMethodSignature($className, $methodName, $variant);
		}

		return true;
	}

	/**
	 * @param string $className
	 * @param string $methodName
	 * @return array{ClassMethod, string}|null
	 * @throws \PHPStan\ShouldNotHappenException
	 */
	private function findMethodNode(string $className, string $methodName): ?array
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
				return $this->methodNodes[$lowerClassName][$lowerMethodName] = [$stmt, $stubFile];
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

	public function getMethodSignature(string $className, string $methodName, ?\ReflectionMethod $reflectionMethod, int $variant = 0): FunctionSignature
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, Php8StubsMap::CLASSES)) {
			return $this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $reflectionMethod, $variant);
		}

		if ($variant > 0) {
			return $this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $reflectionMethod, $variant);
		}

		if ($this->functionSignatureMapProvider->hasMethodSignature($className, $methodName, 1)) {
			return $this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $reflectionMethod, $variant);
		}

		$methodNode = $this->findMethodNode($className, $methodName);
		if ($methodNode === null) {
			return $this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $reflectionMethod, $variant);
		}

		[$methodNode, $stubFile] = $methodNode;

		$signature = $this->getSignature($methodNode, $className, $stubFile);
		if ($this->functionSignatureMapProvider->hasMethodSignature($className, $methodName)) {
			return $this->mergeSignatures(
				$signature,
				$this->functionSignatureMapProvider->getMethodSignature($className, $methodName, $reflectionMethod, $variant)
			);
		}

		return $signature;
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

		if ($this->functionSignatureMapProvider->hasFunctionSignature($functionName, 1)) {
			return $this->functionSignatureMapProvider->getFunctionSignature($functionName, $className, 1);
		}

		$stubFile = self::DIRECTORY . '/' . Php8StubsMap::FUNCTIONS[$lowerName];
		$nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
		$functions = $nodes->getFunctionNodes();
		if (count($functions) !== 1) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
		}

		$signature = $this->getSignature($functions[$lowerName]->getNode(), null, $stubFile);
		if ($this->functionSignatureMapProvider->hasFunctionSignature($functionName)) {
			return $this->mergeSignatures(
				$signature,
				$this->functionSignatureMapProvider->getFunctionSignature($functionName, $className)
			);
		}

		return $signature;
	}

	private function mergeSignatures(FunctionSignature $nativeSignature, FunctionSignature $functionMapSignature): FunctionSignature
	{
		$parameters = [];
		foreach ($nativeSignature->getParameters() as $i => $nativeParameter) {
			if (!array_key_exists($i, $functionMapSignature->getParameters())) {
				$parameters[] = $nativeParameter;
				continue;
			}

			$functionMapParameter = $functionMapSignature->getParameters()[$i];
			$nativeParameterType = $nativeParameter->getNativeType();
			$parameters[] = new ParameterSignature(
				$nativeParameter->getName(),
				$nativeParameter->isOptional(),
				TypehintHelper::decideType(
					$nativeParameterType,
					TypehintHelper::decideType(
						$nativeParameter->getType(),
						$functionMapParameter->getType()
					)
				),
				$nativeParameterType,
				$nativeParameter->passedByReference()->yes() ? $functionMapParameter->passedByReference() : $nativeParameter->passedByReference(),
				$nativeParameter->isVariadic()
			);
		}

		$nativeReturnType = $nativeSignature->getNativeReturnType();
		if ($nativeReturnType instanceof MixedType && !$nativeReturnType->isExplicitMixed()) {
			$returnType = $functionMapSignature->getReturnType();
		} else {
			$returnType = TypehintHelper::decideType(
				$nativeReturnType,
				TypehintHelper::decideType(
					$nativeSignature->getReturnType(),
					$functionMapSignature->getReturnType()
				)
			);
		}

		return new FunctionSignature(
			$parameters,
			$returnType,
			$nativeReturnType,
			$nativeSignature->isVariadic()
		);
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

	/**
	 * @param ClassMethod|Function_ $function
	 * @param string $stubFile
	 * @return FunctionSignature
	 */
	private function getSignature(
		FunctionLike $function,
		?string $className,
		string $stubFile
	): FunctionSignature
	{
		$phpDocParameterTypes = null;
		$phpDocReturnType = null;
		if ($function->getDocComment() !== null) {
			$phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$stubFile,
				$className,
				null,
				$function instanceof ClassMethod ? $function->name->toString() : $function->namespacedName->toString(),
				$function->getDocComment()->getText()
			);
			$phpDocParameterTypes = array_map(static function (ParamTag $param): Type {
				return $param->getType();
			}, $phpDoc->getParamTags());
			if ($phpDoc->getReturnTag() !== null) {
				$phpDocReturnType = $phpDoc->getReturnTag()->getType();
			}
		}
		$parameters = [];
		$variadic = false;
		foreach ($function->getParams() as $param) {
			$name = $param->var;
			if (!$name instanceof Variable || !is_string($name->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$parameterType = ParserNodeTypeToPHPStanType::resolve($param->type, null);
			$parameters[] = new ParameterSignature(
				$name->name,
				$param->default !== null || $param->variadic,
				TypehintHelper::decideType($parameterType, $phpDocParameterTypes[$name->name] ?? null),
				$parameterType,
				$param->byRef ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(),
				$param->variadic
			);

			$variadic = $variadic || $param->variadic;
		}

		$returnType = ParserNodeTypeToPHPStanType::resolve($function->getReturnType(), null);

		return new FunctionSignature(
			$parameters,
			TypehintHelper::decideType($returnType, $phpDocReturnType ?? null),
			$returnType,
			$variadic
		);
	}

}
