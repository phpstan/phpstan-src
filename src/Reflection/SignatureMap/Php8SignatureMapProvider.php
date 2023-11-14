<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
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

	private const DIRECTORY = __DIR__ . '/../../../vendor/phpstan/php-8-stubs';

	/** @var array<string, array<string, array{ClassMethod, string}>> */
	private array $methodNodes = [];

	/** @var array<string, array<string, Type|null>> */
	private array $constantTypes = [];

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
	 */
	private function findMethodNode(string $className, string $methodName): ?array
	{
		$lowerClassName = strtolower($className);
		$lowerMethodName = strtolower($methodName);
		if (isset($this->methodNodes[$lowerClassName][$lowerMethodName])) {
			return $this->methodNodes[$lowerClassName][$lowerMethodName];
		}

		$stubFile = self::DIRECTORY . '/' . $this->map->classes[$lowerClassName];
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
				if (!$this->isForCurrentVersion($stmt->attrGroups)) {
					continue;
				}
				return $this->methodNodes[$lowerClassName][$lowerMethodName] = [$stmt, $stubFile];
			}
		}

		return null;
	}

	/**
	 * @param AttributeGroup[] $attrGroups
	 */
	private function isForCurrentVersion(array $attrGroups): bool
	{
		foreach ($attrGroups as $attrGroup) {
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

		return ['positional' => [$signature], 'named' => null];
	}

	public function getFunctionSignatures(string $functionName, ?string $className, ReflectionFunctionAbstract|null $reflectionFunction): array
	{
		$lowerName = strtolower($functionName);
		if (!array_key_exists($lowerName, $this->map->functions)) {
			return $this->functionSignatureMapProvider->getFunctionSignatures($functionName, $className, $reflectionFunction);
		}

		$stubFile = self::DIRECTORY . '/' . $this->map->functions[$lowerName];
		$nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
		$functions = $nodes->getFunctionNodes();
		if (!array_key_exists($lowerName, $functions)) {
			throw new ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
		}
		foreach ($functions[$lowerName] as $functionNode) {
			if (!$this->isForCurrentVersion($functionNode->getNode()->getAttrGroups())) {
				continue;
			}

			$signature = $this->getSignature($functionNode->getNode(), null, $stubFile);
			if ($this->functionSignatureMapProvider->hasFunctionSignature($functionName)) {
				$functionMapSignatures = $this->functionSignatureMapProvider->getFunctionSignatures($functionName, $className, $reflectionFunction);

				return $this->getMergedSignatures($signature, $functionMapSignatures);
			}

			return ['positional' => [$signature], 'named' => null];
		}

		throw new ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
	}

	/**
	 * @param array{positional: array<int, FunctionSignature>, named: ?array<int, FunctionSignature>} $functionMapSignatures
	 * @return array{positional: array<int, FunctionSignature>, named: ?array<int, FunctionSignature>}
	 */
	private function getMergedSignatures(FunctionSignature $nativeSignature, array $functionMapSignatures): array
	{
		if (count($functionMapSignatures['positional']) === 1) {
			return ['positional' => [$this->mergeSignatures($nativeSignature, $functionMapSignatures['positional'][0])], 'named' => null];
		}

		if (count($functionMapSignatures['positional']) === 0) {
			return ['positional' => [], 'named' => null];
		}

		$nativeParams = $nativeSignature->getParameters();
		$namedArgumentsVariants = [];
		foreach ($functionMapSignatures['positional'] as $functionMapSignature) {
			$isPrevParamVariadic = false;
			$hasMiddleVariadicParam = false;
			// avoid weird functions like array_diff_uassoc
			foreach ($functionMapSignature->getParameters() as $i => $functionParam) {
				$nativeParam = $nativeParams[$i] ?? null;
				$hasMiddleVariadicParam = $hasMiddleVariadicParam || $isPrevParamVariadic;
				$isPrevParamVariadic = $functionParam->isVariadic() || (
					$nativeParam !== null
						? $nativeParam->isVariadic()
						: false
					);
			}

			if ($hasMiddleVariadicParam) {
				continue;
			}

			$parameters = [];
			foreach ($functionMapSignature->getParameters() as $i => $functionParam) {
				if (!array_key_exists($i, $nativeParams)) {
					continue 2;
				}

				$parameters[] = new ParameterSignature(
					$nativeParams[$i]->getName(),
					$functionParam->isOptional(),
					$functionParam->getType(),
					$functionParam->getNativeType(),
					$functionParam->passedByReference(),
					$functionParam->isVariadic(),
					$functionParam->getDefaultValue(),
					$functionParam->getOutType(),
				);
			}

			$namedArgumentsVariants[] = new FunctionSignature(
				$parameters,
				$functionMapSignature->getReturnType(),
				$functionMapSignature->getNativeReturnType(),
				$functionMapSignature->isVariadic(),
			);
		}

		if (count($namedArgumentsVariants) === 0) {
			$namedArgumentsVariants = null;
		}

		return ['positional' => $functionMapSignatures['positional'], 'named' => $namedArgumentsVariants];
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

	public function hasClassConstantMetadata(string $className, string $constantName): bool
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, $this->map->classes)) {
			return false;
		}

		return $this->findConstantType($className, $constantName) !== null;
	}

	public function getClassConstantMetadata(string $className, string $constantName): array
	{
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, $this->map->classes)) {
			throw new ShouldNotHappenException();
		}

		$type = $this->findConstantType($className, $constantName);
		if ($type === null) {
			throw new ShouldNotHappenException();
		}

		return [
			'nativeType' => $type,
		];
	}

	private function findConstantType(string $className, string $constantName): ?Type
	{
		$lowerClassName = strtolower($className);
		$lowerConstantName = strtolower($constantName);
		if (isset($this->constantTypes[$lowerClassName][$lowerConstantName])) {
			return $this->constantTypes[$lowerClassName][$lowerConstantName];
		}

		$stubFile = self::DIRECTORY . '/' . $this->map->classes[$lowerClassName];
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
			if (!$stmt instanceof ClassConst) {
				continue;
			}

			foreach ($stmt->consts as $const) {
				if ($const->name->toString() !== $constantName) {
					continue;
				}

				if (!$this->isForCurrentVersion($stmt->attrGroups)) {
					continue;
				}

				if ($stmt->type === null) {
					return null;
				}

				return $this->constantTypes[$lowerClassName][$lowerConstantName] = ParserNodeTypeToPHPStanType::resolve($stmt->type, null);
			}
		}

		return null;
	}

}
