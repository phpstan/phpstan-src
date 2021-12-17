<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Closure;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Exception\NotAClassReflection;
use PHPStan\BetterReflection\Reflection\Exception\NotAnInterfaceReflection;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Broker\ConstantNotFoundException;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
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
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use ReflectionParameter;
use function array_key_exists;
use function array_map;
use function sprintf;
use function strtolower;
use const PHP_VERSION_ID;

class BetterReflectionProvider implements ReflectionProvider
{

	private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider;

	private ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider;

	private Reflector $reflector;

	private FileTypeMapper $fileTypeMapper;

	private PhpDocInheritanceResolver $phpDocInheritanceResolver;

	private PhpVersion $phpVersion;

	private NativeFunctionReflectionProvider $nativeFunctionReflectionProvider;

	private StubPhpDocProvider $stubPhpDocProvider;

	private FunctionReflectionFactory $functionReflectionFactory;

	private RelativePathHelper $relativePathHelper;

	private AnonymousClassNameHelper $anonymousClassNameHelper;

	private Standard $printer;

	private FileHelper $fileHelper;

	private PhpStormStubsSourceStubber $phpstormStubsSourceStubber;

	/** @var FunctionReflection[] */
	private array $functionReflections = [];

	/** @var ClassReflection[] */
	private array $classReflections = [];

	/** @var ClassReflection[] */
	private static array $anonymousClasses = [];

	/** @var array<string, GlobalConstantReflection> */
	private array $cachedConstants = [];

	public function __construct(
		ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider,
		Reflector $reflector,
		FileTypeMapper $fileTypeMapper,
		PhpDocInheritanceResolver $phpDocInheritanceResolver,
		PhpVersion $phpVersion,
		NativeFunctionReflectionProvider $nativeFunctionReflectionProvider,
		StubPhpDocProvider $stubPhpDocProvider,
		FunctionReflectionFactory $functionReflectionFactory,
		RelativePathHelper $relativePathHelper,
		AnonymousClassNameHelper $anonymousClassNameHelper,
		Standard $printer,
		FileHelper $fileHelper,
		PhpStormStubsSourceStubber $phpstormStubsSourceStubber
	)
	{
		$this->reflectionProviderProvider = $reflectionProviderProvider;
		$this->classReflectionExtensionRegistryProvider = $classReflectionExtensionRegistryProvider;
		$this->reflector = $reflector;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->phpDocInheritanceResolver = $phpDocInheritanceResolver;
		$this->phpVersion = $phpVersion;
		$this->nativeFunctionReflectionProvider = $nativeFunctionReflectionProvider;
		$this->stubPhpDocProvider = $stubPhpDocProvider;
		$this->functionReflectionFactory = $functionReflectionFactory;
		$this->relativePathHelper = $relativePathHelper;
		$this->anonymousClassNameHelper = $anonymousClassNameHelper;
		$this->printer = $printer;
		$this->fileHelper = $fileHelper;
		$this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
	}

	public function hasClass(string $className): bool
	{
		if (isset(self::$anonymousClasses[$className])) {
			return true;
		}

		if (!ClassNameHelper::isValidClassName($className)) {
			return false;
		}

		try {
			$this->reflector->reflectClass($className);
			return true;
		} catch (IdentifierNotFound $e) {
			return false;
		} catch (InvalidIdentifierName $e) {
			return false;
		}
	}

	public function getClass(string $className): ClassReflection
	{
		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className];
		}

		try {
			$reflectionClass = $this->reflector->reflectClass($className);
		} catch (IdentifierNotFound $e) {
			throw new ClassNotFoundException($className);
		}

		$reflectionClassName = strtolower($reflectionClass->getName());

		if (array_key_exists($reflectionClassName, $this->classReflections)) {
			return $this->classReflections[$reflectionClassName];
		}

		$classReflection = new ClassReflection(
			$this->reflectionProviderProvider->getReflectionProvider(),
			$this->fileTypeMapper,
			$this->stubPhpDocProvider,
			$this->phpDocInheritanceResolver,
			$this->phpVersion,
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getPropertiesClassReflectionExtensions(),
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getMethodsClassReflectionExtensions(),
			$reflectionClass->getName(),
			$reflectionClass instanceof \PHPStan\BetterReflection\Reflection\ReflectionEnum && PHP_VERSION_ID >= 80000 ? new ReflectionEnum($reflectionClass) : new ReflectionClass($reflectionClass),
			null,
			null,
			$this->stubPhpDocProvider->findClassPhpDoc($reflectionClass->getName()),
		);

		$this->classReflections[$reflectionClassName] = $classReflection;

		return $classReflection;
	}

	public function getClassName(string $className): string
	{
		if (!$this->hasClass($className)) {
			throw new ClassNotFoundException($className);
		}

		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className]->getDisplayName();
		}

		$reflectionClass = $this->reflector->reflectClass($className);

		return $reflectionClass->getName();
	}

	public function supportsAnonymousClasses(): bool
	{
		return true;
	}

	public function getAnonymousClassReflection(Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		if (isset($classNode->namespacedName)) {
			throw new ShouldNotHappenException();
		}

		if (!$scope->isInTrait()) {
			$scopeFile = $scope->getFile();
		} else {
			$scopeFile = $scope->getTraitReflection()->getFileName();
			if ($scopeFile === null) {
				$scopeFile = $scope->getFile();
			}
		}

		$filename = $this->fileHelper->normalizePath($this->relativePathHelper->getRelativePath($scopeFile), '/');
		$className = $this->anonymousClassNameHelper->getAnonymousClassName(
			$classNode,
			$scopeFile,
		);
		$classNode->name = new Node\Identifier($className);
		$classNode->setAttribute('anonymousClass', true);

		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className];
		}

		$reflectionClass = \PHPStan\BetterReflection\Reflection\ReflectionClass::createFromNode(
			$this->reflector,
			$classNode,
			new LocatedSource($this->printer->prettyPrint([$classNode]), $className, $scopeFile),
			null,
		);

		self::$anonymousClasses[$className] = new ClassReflection(
			$this->reflectionProviderProvider->getReflectionProvider(),
			$this->fileTypeMapper,
			$this->stubPhpDocProvider,
			$this->phpDocInheritanceResolver,
			$this->phpVersion,
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getPropertiesClassReflectionExtensions(),
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getMethodsClassReflectionExtensions(),
			sprintf('class@anonymous/%s:%s', $filename, $classNode->getLine()),
			new ReflectionClass($reflectionClass),
			$scopeFile,
			null,
			$this->stubPhpDocProvider->findClassPhpDoc($className),
		);
		$this->classReflections[$className] = self::$anonymousClasses[$className];

		return self::$anonymousClasses[$className];
	}

	public function hasFunction(Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveFunctionName($nameNode, $scope) !== null;
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

		$this->functionReflections[$lowerCasedFunctionName] = $this->getCustomFunction($functionName);

		return $this->functionReflections[$lowerCasedFunctionName];
	}

	private function getCustomFunction(string $functionName): PhpFunctionReflection
	{
		$reflectionFunction = new ReflectionFunction($this->reflector->reflectFunction($functionName));
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
			$docComment = $reflectionFunction->getDocComment();
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($reflectionFunction->getFileName(), null, null, $reflectionFunction->getName(), $docComment);
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

		return $this->functionReflectionFactory->create(
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
	}

	public function resolveFunctionName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			try {
				$this->reflector->reflectFunction($name);
				return true;
			} catch (IdentifierNotFound $e) {
				// pass
			} catch (InvalidIdentifierName $e) {
				// pass
			}

			if ($this->nativeFunctionReflectionProvider->findFunctionReflection($name) !== null) {
				return $this->phpstormStubsSourceStubber->isPresentFunction($name) !== false;
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

		$constantReflection = $this->reflector->reflectConstant($constantName);
		try {
			$constantValue = $constantReflection->getValue();
			$constantValueType = ConstantTypeHelper::getTypeFromValue($constantValue);
			$fileName = $constantReflection->getFileName();
		} catch (UnableToCompileNode | NotAClassReflection | NotAnInterfaceReflection $e) {
			$constantValueType = new MixedType();
			$fileName = null;
		}

		return $this->cachedConstants[$constantName] = new RuntimeConstantReflection(
			$constantName,
			$constantValueType,
			$fileName,
		);
	}

	public function resolveConstantName(Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			try {
				$this->reflector->reflectConstant($name);
				return true;
			} catch (IdentifierNotFound $e) {
				// pass
			} catch (UnableToCompileNode | NotAClassReflection | NotAnInterfaceReflection $e) {
				// pass
			}
			return false;
		}, $scope);
	}

	/**
	 * @param Closure(string $name): bool $existsCallback
	 */
	private function resolveName(
		Node\Name $nameNode,
		Closure $existsCallback,
		?Scope $scope
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

}
