<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Runtime;

use Closure;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Broker\ClassAutoloadingException;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Broker\ConstantNotFoundException;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\ClassNameHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Constant\RuntimeConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Throwable;
use function array_key_exists;
use function array_map;
use function class_exists;
use function constant;
use function debug_backtrace;
use function defined;
use function function_exists;
use function interface_exists;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function sprintf;
use function strtolower;
use function trait_exists;
use function trim;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

class RuntimeReflectionProvider implements ReflectionProvider
{

	/** @var ClassReflection[] */
	private array $classReflections = [];

	/** @var FunctionReflection[] */
	private array $functionReflections = [];

	/** @var PhpFunctionReflection[] */
	private array $customFunctionReflections = [];

	/** @var bool[] */
	private array $hasClassCache = [];

	/** @var ClassReflection[] */
	private static array $anonymousClasses = [];

	/** @var array<string, GlobalConstantReflection> */
	private array $cachedConstants = [];

	public function __construct(
		private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		private ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider,
		private FunctionReflectionFactory $functionReflectionFactory,
		private FileTypeMapper $fileTypeMapper,
		private PhpDocInheritanceResolver $phpDocInheritanceResolver,
		private PhpVersion $phpVersion,
		private NativeFunctionReflectionProvider $nativeFunctionReflectionProvider,
		private StubPhpDocProvider $stubPhpDocProvider,
		private PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
	)
	{
	}

	public function getClass(string $className): ClassReflection
	{
		/** @var class-string $className */
		$className = $className;
		if (!$this->hasClass($className)) {
			throw new ClassNotFoundException($className);
		}

		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className];
		}

		if (!isset($this->classReflections[$className])) {
			$reflectionClass = new ReflectionClass($className);
			$filename = null;
			if ($reflectionClass->getFileName() !== false) {
				$filename = $reflectionClass->getFileName();
			}

			$classReflection = $this->getClassFromReflection(
				$reflectionClass,
				$reflectionClass->getName(),
				$reflectionClass->isAnonymous() ? $filename : null,
			);
			$this->classReflections[$className] = $classReflection;
			if ($className !== $reflectionClass->getName()) {
				// class alias optimization
				$this->classReflections[$reflectionClass->getName()] = $classReflection;
			}
		}

		return $this->classReflections[$className];
	}

	public function getClassName(string $className): string
	{
		if (!$this->hasClass($className)) {
			throw new ClassNotFoundException($className);
		}

		/** @var class-string $className */
		$className = $className;
		$reflectionClass = new ReflectionClass($className);
		$realName = $reflectionClass->getName();

		if (isset(self::$anonymousClasses[$realName])) {
			return self::$anonymousClasses[$realName]->getDisplayName();
		}

		return $realName;
	}

	public function supportsAnonymousClasses(): bool
	{
		return false;
	}

	public function getAnonymousClassReflection(
		Node\Stmt\Class_ $classNode,
		Scope $scope,
	): ClassReflection
	{
		throw new ShouldNotHappenException();
	}

	/**
	 * @param ReflectionClass<object> $reflectionClass
	 */
	private function getClassFromReflection(ReflectionClass $reflectionClass, string $displayName, ?string $anonymousFilename): ClassReflection
	{
		$className = $reflectionClass->getName();
		if (!isset($this->classReflections[$className])) {
			$classReflection = new ClassReflection(
				$this->reflectionProviderProvider->getReflectionProvider(),
				$this->fileTypeMapper,
				$this->stubPhpDocProvider,
				$this->phpDocInheritanceResolver,
				$this->phpVersion,
				$this->classReflectionExtensionRegistryProvider->getRegistry()->getPropertiesClassReflectionExtensions(),
				$this->classReflectionExtensionRegistryProvider->getRegistry()->getMethodsClassReflectionExtensions(),
				$displayName,
				$reflectionClass,
				$anonymousFilename,
				null,
				$this->stubPhpDocProvider->findClassPhpDoc($className),
			);
			$this->classReflections[$className] = $classReflection;
		}

		return $this->classReflections[$className];
	}

	public function hasClass(string $className): bool
	{
		if (!ClassNameHelper::isValidClassName($className)) {
			return $this->hasClassCache[$className] = false;
		}

		$className = trim($className, '\\');
		if (isset($this->hasClassCache[$className])) {
			return $this->hasClassCache[$className];
		}

		spl_autoload_register($autoloader = function (string $autoloadedClassName) use ($className): void {
			$autoloadedClassName = trim($autoloadedClassName, '\\');
			if ($autoloadedClassName !== $className && !$this->isExistsCheckCall()) {
				throw new ClassAutoloadingException($autoloadedClassName);
			}
		});

		try {
			return $this->hasClassCache[$className] = class_exists($className) || interface_exists($className) || trait_exists($className);
		} catch (ClassAutoloadingException $e) {
			throw $e;
		} catch (Throwable $t) {
			throw new ClassAutoloadingException(
				$className,
				$t,
			);
		} finally {
			spl_autoload_unregister($autoloader);
		}
	}

	public function getFunction(Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		if ($functionName === null) {
			throw new FunctionNotFoundException((string) $nameNode);
		}

		$lowerCasedFunctionName = strtolower($functionName);
		if (isset($this->functionReflections[$lowerCasedFunctionName])) {
			return $this->functionReflections[$lowerCasedFunctionName];
		}

		$nativeFunctionReflection = $this->nativeFunctionReflectionProvider->findFunctionReflection($lowerCasedFunctionName);
		if ($nativeFunctionReflection !== null) {
			$this->functionReflections[$lowerCasedFunctionName] = $nativeFunctionReflection;
			return $nativeFunctionReflection;
		}

		$this->functionReflections[$lowerCasedFunctionName] = $this->getCustomFunction($nameNode, $scope);

		return $this->functionReflections[$lowerCasedFunctionName];
	}

	public function hasFunction(Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveFunctionName($nameNode, $scope) !== null;
	}

	private function hasCustomFunction(Node\Name $nameNode, ?Scope $scope): bool
	{
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		if ($functionName === null) {
			return false;
		}

		return $this->nativeFunctionReflectionProvider->findFunctionReflection($functionName) === null;
	}

	private function getCustomFunction(Node\Name $nameNode, ?Scope $scope): PhpFunctionReflection
	{
		if (!$this->hasCustomFunction($nameNode, $scope)) {
			throw new FunctionNotFoundException((string) $nameNode);
		}

		/** @var string $functionName */
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		if (!function_exists($functionName)) {
			throw new FunctionNotFoundException($functionName);
		}
		$lowerCasedFunctionName = strtolower($functionName);
		if (isset($this->customFunctionReflections[$lowerCasedFunctionName])) {
			return $this->customFunctionReflections[$lowerCasedFunctionName];
		}

		$reflectionFunction = new ReflectionFunction($functionName);
		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTags = [];
		$phpDocReturnTag = null;
		$phpDocThrowsTag = null;
		$deprecatedTag = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		$isPure = null;
		$resolvedPhpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($reflectionFunction->getName(), array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $reflectionFunction->getParameters()));
		if ($resolvedPhpDoc === null && $reflectionFunction->getFileName() !== false && $reflectionFunction->getDocComment() !== false) {
			$fileName = $reflectionFunction->getFileName();
			$docComment = $reflectionFunction->getDocComment();
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, $reflectionFunction->getName(), $docComment);
		}

		if ($resolvedPhpDoc !== null) {
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
			$phpDocParameterTags = $resolvedPhpDoc->getParamTags();
			$phpDocReturnTag = $resolvedPhpDoc->getReturnTag();
			$phpDocThrowsTag = $resolvedPhpDoc->getThrowsTag();
			$deprecatedTag = $resolvedPhpDoc->getDeprecatedTag();
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$isFinal = $resolvedPhpDoc->isFinal();
			$isPure = $resolvedPhpDoc->isPure();
		}

		$functionReflection = $this->functionReflectionFactory->create(
			$reflectionFunction,
			$templateTypeMap,
			array_map(static fn (ParamTag $paramTag): Type => $paramTag->getType(), $phpDocParameterTags),
			$phpDocReturnTag !== null ? $phpDocReturnTag->getType() : null,
			$phpDocThrowsTag !== null ? $phpDocThrowsTag->getType() : null,
			$deprecatedTag !== null ? $deprecatedTag->getMessage() : null,
			$isDeprecated,
			$isInternal,
			$isFinal,
			$reflectionFunction->getFileName() !== false ? $reflectionFunction->getFileName() : null,
			$isPure,
		);
		$this->customFunctionReflections[$lowerCasedFunctionName] = $functionReflection;

		return $functionReflection;
	}

	public function resolveFunctionName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			$exists = function_exists($name) || $this->nativeFunctionReflectionProvider->findFunctionReflection($name) !== null;
			if ($exists) {
				if ($this->phpStormStubsSourceStubber->isPresentFunction($name) === false) {
					return false;
				}

				return true;
			}

			return false;
		}, $scope);
	}

	public function hasConstant(Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveConstantName($nameNode, $scope) !== null;
	}

	public function getConstant(Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		$constantName = $this->resolveConstantName($nameNode, $scope);
		if ($constantName === null) {
			throw new ConstantNotFoundException((string) $nameNode);
		}

		if (array_key_exists($constantName, $this->cachedConstants)) {
			return $this->cachedConstants[$constantName];
		}

		return $this->cachedConstants[$constantName] = new RuntimeConstantReflection(
			$constantName,
			ConstantTypeHelper::getTypeFromValue(@constant($constantName)),
			null,
		);
	}

	public function resolveConstantName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, static fn (string $name): bool => defined($name), $scope);
	}

	/**
	 * @param Closure(string $name): bool $existsCallback
	 */
	private function resolveName(
		Node\Name $nameNode,
		Closure $existsCallback,
		?Scope $scope,
	): ?string
	{
		$name = (string) $nameNode;
		if ($scope !== null && $scope->getNamespace() !== null && !$nameNode->isFullyQualified()) {
			$namespacedName = sprintf('%s\\%s', $scope->getNamespace(), $name);
			if ($existsCallback($namespacedName)) {
				return $namespacedName;
			}
		}

		if ($existsCallback($name)) {
			return $name;
		}

		return null;
	}

	private function isExistsCheckCall(): bool
	{
		$debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$existsCallTypes = [
			'class_exists' => true,
			'interface_exists' => true,
			'trait_exists' => true,
		];

		foreach ($debugBacktrace as $traceStep) {
			if (
				isset($existsCallTypes[$traceStep['function']])
				// We must ignore the self::hasClass calls
				&& (!isset($traceStep['file']) || $traceStep['file'] !== __FILE__)
			) {
				return true;
			}
		}

		return false;
	}

}
