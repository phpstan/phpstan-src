<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\Php\PhpVersion;
use PHPStan\Php8StubsMap;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PassedByReference;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use ReflectionFunctionAbstract;
use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function is_string;
use function sprintf;
use function strtolower;

class Php8SignatureMapProvider implements SignatureMapProvider
{
	/** @var array<string, array<string, array{ClassMethod, string}>> */
	private array $methodNodes = [];

	private Php8StubsMap $map;

	public function __construct(
		private FunctionSignatureMapProvider $functionSignatureMapProvider,
		private FileNodesFetcher $fileNodesFetcher,
		private FileTypeMapper $fileTypeMapper,
		private PhpVersion $phpVersion,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
		$this->map = new Php8StubsMap($phpVersion->getVersionId());
	}

	public function hasMethodSignature(string $className, string $methodName): bool
	{
		$lowerClassName = strtolower($className);
		if ($lowerClassName === 'backedenum') {
			return false;
		}
		if (!array_key_exists($lowerClassName, $this->map->classes)) {
			return $this->functionSignatureMapProvider->hasMethodSignature($className, $methodName);
		}

		if ($this->findMethodNode($className, $methodName) === null) {
			return $this->functionSignatureMapProvider->hasMethodSignature($className, $methodName);
		}

		return true;
	}

	/**
	 * @return array{ClassMethod, string}|null
	 * @throws ShouldNotHappenException
	 */
	private function findMethodNode(string $className, string $methodName): ?array
	{
		$lowerClassName = strtolower($className);
		$lowerMethodName = strtolower($methodName);
		if (isset($this->methodNodes[$lowerClassName][$lowerMethodName])) {
			return $this->methodNodes[$lowerClassName][$lowerMethodName];
		}

		$stubFile = $this->map->classes[$lowerClassName];
		$nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
		$classes = $nodes->getClassNodes();
		if (count($classes) !== 1) {
			throw new ShouldNotHappenException(sprintf('Class %s stub not found in %s.', $className, $stubFile));
		}

		$class = $classes[$lowerClassName];
		if (count($class) !== 1) {
			throw new ShouldNotHappenException(sprintf('Class %s stub not found in %s.', $className, $stubFile));
		}

		foreach ($class[0]->getNode()->stmts as $stmt) {
			if (!$stmt instanceof ClassMethod) {
				continue;
			}

			if ($stmt->name->toLowerString() === $lowerMethodName) {
				if (!$this->isForCurrentVersion($stmt)) {
					continue;
				}
				return $this->methodNodes[$lowerClassName][$lowerMethodName] = [$stmt, $stubFile];
			}
		}

		return null;
	}

	private function isForCurrentVersion(FunctionLike $functionLike): bool
	{
		foreach ($functionLike->getAttrGroups() as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toString() === 'Until') {
					$arg = $attr->args[0]->value;
					if (!$arg instanceof String_) {
						throw new ShouldNotHappenException();
					}
					$parts = explode('.', $arg->value);
					$versionId = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
					if ($this->phpVersion->getVersionId() >= $versionId) {
						return false;
					}
				}
				if ($attr->name->toString() !== 'Since') {
					continue;
				}

				$arg = $attr->args[0]->value;
				if (!$arg instanceof String_) {
					throw new ShouldNotHappenException();
				}
				$parts = explode('.', $arg->value);
				$versionId = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
				if ($this->phpVersion->getVersionId() < $versionId) {
					return false;
				}
			}
		}

		return true;
	}

	public function hasFunctionSignature(string $name): bool
	{
		$lowerName = strtolower($name);
		if (!array_key_exists($lowerName, $this->map->functions)) {
			return $this->functionSignatureMapProvider->hasFunctionSignature($name);
		}

		return true;
	}

	public function getMethodSignatures(string $className, string $methodName, ?ReflectionMethod $reflectionMethod): array
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, $this->map->classes)) {
			return $this->functionSignatureMapProvider->getMethodSignatures($className, $methodName, $reflectionMethod);
		}

		$methodNode = $this->findMethodNode($className, $methodName);
		if ($methodNode === null) {
			return $this->functionSignatureMapProvider->getMethodSignatures($className, $methodName, $reflectionMethod);
		}

		[$methodNode, $stubFile] = $methodNode;

		$signature = $this->getSignature($methodNode, $className, $stubFile);
		if ($this->functionSignatureMapProvider->hasMethodSignature($className, $methodName)) {
			$functionMapSignatures = $this->functionSignatureMapProvider->getMethodSignatures($className, $methodName, $reflectionMethod);

			return $this->getMergedSignatures($signature, $functionMapSignatures);
		}

		return [$signature];
	}

	public function getFunctionSignatures(string $functionName, ?string $className, ReflectionFunctionAbstract|null $reflectionFunction): array
	{
		$lowerName = strtolower($functionName);
		if (!array_key_exists($lowerName, $this->map->functions)) {
			return $this->functionSignatureMapProvider->getFunctionSignatures($functionName, $className, $reflectionFunction);
		}

		$stubFile = $this->map->functions[$lowerName];
		$nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
		$functions = $nodes->getFunctionNodes();
		if (!array_key_exists($lowerName, $functions)) {
			throw new ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
		}
		foreach ($functions[$lowerName] as $functionNode) {
			if (!$this->isForCurrentVersion($functionNode->getNode())) {
				continue;
			}

			$signature = $this->getSignature($functionNode->getNode(), null, $stubFile);
			if ($this->functionSignatureMapProvider->hasFunctionSignature($functionName)) {
				$functionMapSignatures = $this->functionSignatureMapProvider->getFunctionSignatures($functionName, $className, $reflectionFunction);

				return $this->getMergedSignatures($signature, $functionMapSignatures);
			}

			return [$signature];
		}

		throw new ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
	}

	/**
	 * @param array<int, FunctionSignature> $functionMapSignatures
	 * @return array<int, FunctionSignature>
	 */
	private function getMergedSignatures(FunctionSignature $nativeSignature, array $functionMapSignatures): array
	{
		if (count($functionMapSignatures) === 1) {
			return [$this->mergeSignatures($nativeSignature, $functionMapSignatures[0])];
		}

		return $functionMapSignatures;
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
						$functionMapParameter->getType(),
					),
				),
				$nativeParameterType,
				$nativeParameter->passedByReference()->yes() ? $functionMapParameter->passedByReference() : $nativeParameter->passedByReference(),
				$nativeParameter->isVariadic(),
				$nativeParameter->getDefaultValue(),
				$nativeParameter->getOutType(),
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
					$functionMapSignature->getReturnType(),
				),
			);
		}

		return new FunctionSignature(
			$parameters,
			$returnType,
			$nativeReturnType,
			$nativeSignature->isVariadic(),
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
	 * @return array{hasSideEffects: bool}
	 */
	public function getMethodMetadata(string $className, string $methodName): array
	{
		return $this->functionSignatureMapProvider->getMethodMetadata($className, $methodName);
	}

	/**
	 * @return array{hasSideEffects: bool}
	 */
	public function getFunctionMetadata(string $functionName): array
	{
		return $this->functionSignatureMapProvider->getFunctionMetadata($functionName);
	}

	private function getSignature(
		ClassMethod|Function_ $function,
		?string $className,
		string $stubFile,
	): FunctionSignature
	{
		$phpDocParameterTypes = null;
		$phpDocReturnType = null;
		if ($function->getDocComment() !== null) {
			if ($function instanceof ClassMethod) {
				$functionName = $function->name->toString();
			} elseif ($function->namespacedName !== null) {
				$functionName = $function->namespacedName->toString();
			} else {
				throw new ShouldNotHappenException();
			}
			$phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$stubFile,
				$className,
				null,
				$functionName,
				$function->getDocComment()->getText(),
			);
			$phpDocParameterTypes = array_map(static fn (ParamTag $param): Type => $param->getType(), $phpDoc->getParamTags());
			if ($phpDoc->getReturnTag() !== null) {
				$phpDocReturnType = $phpDoc->getReturnTag()->getType();
			}
		}
		$parameters = [];
		$variadic = false;
		foreach ($function->getParams() as $param) {
			$name = $param->var;
			if (!$name instanceof Variable || !is_string($name->name)) {
				throw new ShouldNotHappenException();
			}
			$parameterType = ParserNodeTypeToPHPStanType::resolve($param->type, null);
			$parameters[] = new ParameterSignature(
				$name->name,
				$param->default !== null || $param->variadic,
				TypehintHelper::decideType($parameterType, $phpDocParameterTypes[$name->name] ?? null),
				$parameterType,
				$param->byRef ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(),
				$param->variadic,
				$param->default !== null ? $this->initializerExprTypeResolver->getType(
					$param->default,
					InitializerExprContext::fromStubParameter($className, $stubFile, $function),
				) : null,
				null,
			);

			$variadic = $variadic || $param->variadic;
		}

		$returnType = ParserNodeTypeToPHPStanType::resolve($function->getReturnType(), null);

		return new FunctionSignature(
			$parameters,
			TypehintHelper::decideType($returnType, $phpDocReturnType ?? null),
			$returnType,
			$variadic,
		);
	}

}
