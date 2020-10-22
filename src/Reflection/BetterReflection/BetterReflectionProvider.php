<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Constant\RuntimeConstantReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\GlobalConstantReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Roave\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\Adapter\ReflectionClass;
use Roave\BetterReflection\Reflection\Adapter\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

class BetterReflectionProvider implements ReflectionProvider
{

	private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider;

	private \PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider;

	private \Roave\BetterReflection\Reflector\ClassReflector $classReflector;

	private \Roave\BetterReflection\Reflector\FunctionReflector $functionReflector;

	private \Roave\BetterReflection\Reflector\ConstantReflector $constantReflector;

	private \PHPStan\Type\FileTypeMapper $fileTypeMapper;

	private PhpVersion $phpVersion;

	private \PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider $nativeFunctionReflectionProvider;

	private StubPhpDocProvider $stubPhpDocProvider;

	private \PHPStan\Reflection\FunctionReflectionFactory $functionReflectionFactory;

	private RelativePathHelper $relativePathHelper;

	private AnonymousClassNameHelper $anonymousClassNameHelper;

	private \PhpParser\PrettyPrinter\Standard $printer;

	private \PHPStan\File\FileHelper $fileHelper;

	/** @var \PHPStan\Reflection\FunctionReflection[] */
	private array $functionReflections = [];

	/** @var \PHPStan\Reflection\ClassReflection[] */
	private array $classReflections = [];

	/** @var \PHPStan\Reflection\ClassReflection[] */
	private static array $anonymousClasses = [];

	public function __construct(
		ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		ClassReflectionExtensionRegistryProvider $classReflectionExtensionRegistryProvider,
		ClassReflector $classReflector,
		FileTypeMapper $fileTypeMapper,
		PhpVersion $phpVersion,
		NativeFunctionReflectionProvider $nativeFunctionReflectionProvider,
		StubPhpDocProvider $stubPhpDocProvider,
		FunctionReflectionFactory $functionReflectionFactory,
		RelativePathHelper $relativePathHelper,
		AnonymousClassNameHelper $anonymousClassNameHelper,
		Standard $printer,
		FileHelper $fileHelper,
		FunctionReflector $functionReflector,
		ConstantReflector $constantReflector
	)
	{
		$this->reflectionProviderProvider = $reflectionProviderProvider;
		$this->classReflectionExtensionRegistryProvider = $classReflectionExtensionRegistryProvider;
		$this->classReflector = $classReflector;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->phpVersion = $phpVersion;
		$this->nativeFunctionReflectionProvider = $nativeFunctionReflectionProvider;
		$this->stubPhpDocProvider = $stubPhpDocProvider;
		$this->functionReflectionFactory = $functionReflectionFactory;
		$this->relativePathHelper = $relativePathHelper;
		$this->anonymousClassNameHelper = $anonymousClassNameHelper;
		$this->printer = $printer;
		$this->fileHelper = $fileHelper;
		$this->functionReflector = $functionReflector;
		$this->constantReflector = $constantReflector;
	}

	public function hasClass(string $className): bool
	{
		if (isset(self::$anonymousClasses[$className])) {
			return true;
		}

		try {
			$this->classReflector->reflect($className);
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
			$reflectionClass = $this->classReflector->reflect($className);
		} catch (IdentifierNotFound $e) {
			throw new \PHPStan\Broker\ClassNotFoundException($className);
		}

		$reflectionClassName = strtolower($reflectionClass->getName());

		if (array_key_exists($reflectionClassName, $this->classReflections)) {
			return $this->classReflections[$reflectionClassName];
		}

		$classReflection = new ClassReflection(
			$this->reflectionProviderProvider->getReflectionProvider(),
			$this->fileTypeMapper,
			$this->phpVersion,
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getPropertiesClassReflectionExtensions(),
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getMethodsClassReflectionExtensions(),
			$reflectionClass->getName(),
			new ReflectionClass($reflectionClass),
			null,
			null,
			$this->stubPhpDocProvider->findClassPhpDoc($className)
		);

		$this->classReflections[$reflectionClassName] = $classReflection;

		return $classReflection;
	}

	public function getClassName(string $className): string
	{
		if (!$this->hasClass($className)) {
			throw new \PHPStan\Broker\ClassNotFoundException($className);
		}

		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className]->getDisplayName();
		}

		$reflectionClass = $this->classReflector->reflect($className);

		return $reflectionClass->getName();
	}

	public function supportsAnonymousClasses(): bool
	{
		return true;
	}

	public function getAnonymousClassReflection(\PhpParser\Node\Stmt\Class_ $classNode, Scope $scope): ClassReflection
	{
		if (isset($classNode->namespacedName)) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if (!$scope->isInTrait()) {
			$scopeFile = $scope->getFile();
		} else {
			$scopeFile = $scope->getTraitReflection()->getFileName();
			if ($scopeFile === false) {
				$scopeFile = $scope->getFile();
			}
		}

		$filename = $this->fileHelper->normalizePath($this->relativePathHelper->getRelativePath($scopeFile), '/');
		$className = $this->anonymousClassNameHelper->getAnonymousClassName(
			$classNode,
			$scopeFile
		);
		$classNode->name = new \PhpParser\Node\Identifier($className);
		$classNode->setAttribute('anonymousClass', true);

		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className];
		}

		$reflectionClass = \Roave\BetterReflection\Reflection\ReflectionClass::createFromNode(
			$this->classReflector,
			$classNode,
			new LocatedSource($this->printer->prettyPrint([$classNode]), $scopeFile),
			null
		);

		self::$anonymousClasses[$className] = new ClassReflection(
			$this->reflectionProviderProvider->getReflectionProvider(),
			$this->fileTypeMapper,
			$this->phpVersion,
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getPropertiesClassReflectionExtensions(),
			$this->classReflectionExtensionRegistryProvider->getRegistry()->getMethodsClassReflectionExtensions(),
			sprintf('class@anonymous/%s:%s', $filename, $classNode->getLine()),
			new ReflectionClass($reflectionClass),
			$scopeFile,
			null,
			$this->stubPhpDocProvider->findClassPhpDoc($className)
		);
		$this->classReflections[$className] = self::$anonymousClasses[$className];

		return self::$anonymousClasses[$className];
	}

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveFunctionName($nameNode, $scope) !== null;
	}

	public function getFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		if ($functionName === null) {
			throw new \PHPStan\Broker\FunctionNotFoundException((string) $nameNode);
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

	private function getCustomFunction(string $functionName): \PHPStan\Reflection\Php\PhpFunctionReflection
	{
		$reflectionFunction = new ReflectionFunction($this->functionReflector->reflect($functionName));
		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTags = [];
		$phpDocReturnTag = null;
		$phpDocThrowsTag = null;
		$deprecatedTag = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		$resolvedPhpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($reflectionFunction->getName());
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
		}

		return $this->functionReflectionFactory->create(
			$reflectionFunction,
			$templateTypeMap,
			array_map(static function (ParamTag $paramTag): Type {
				return $paramTag->getType();
			}, $phpDocParameterTags),
			$phpDocReturnTag !== null ? $phpDocReturnTag->getType() : null,
			$phpDocThrowsTag !== null ? $phpDocThrowsTag->getType() : null,
			$deprecatedTag !== null ? $deprecatedTag->getMessage() : null,
			$isDeprecated,
			$isInternal,
			$isFinal,
			$reflectionFunction->getFileName()
		);
	}

	public function resolveFunctionName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			try {
				$this->functionReflector->reflect($name);
				return true;
			} catch (\Roave\BetterReflection\Reflector\Exception\IdentifierNotFound $e) {
				// pass
			} catch (InvalidIdentifierName $e) {
				// pass
			}

			return false;
		}, $scope);
	}

	public function hasConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveConstantName($nameNode, $scope) !== null;
	}

	public function getConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): GlobalConstantReflection
	{
		$constantName = $this->resolveConstantName($nameNode, $scope);
		if ($constantName === null) {
			throw new \PHPStan\Broker\ConstantNotFoundException((string) $nameNode);
		}

		$constantReflection = $this->constantReflector->reflect($constantName);
		try {
			$constantValue = $constantReflection->getValue();
			$constantValueType = ConstantTypeHelper::getTypeFromValue($constantValue);
			$fileName = $constantReflection->getFileName();
		} catch (UnableToCompileNode $e) {
			$constantValueType = new MixedType();
			$fileName = null;
		}

		return new RuntimeConstantReflection(
			$constantName,
			$constantValueType,
			$fileName
		);
	}

	public function resolveConstantName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			try {
				$this->constantReflector->reflect($name);
				return true;
			} catch (\Roave\BetterReflection\Reflector\Exception\IdentifierNotFound $e) {
				// pass
			} catch (UnableToCompileNode $e) {
				// pass
			}
			return false;
		}, $scope);
	}

	/**
	 * @param \PhpParser\Node\Name $nameNode
	 * @param \Closure(string $name): bool $existsCallback
	 * @param Scope|null $scope
	 * @return string|null
	 */
	private function resolveName(
		\PhpParser\Node\Name $nameNode,
		\Closure $existsCallback,
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
