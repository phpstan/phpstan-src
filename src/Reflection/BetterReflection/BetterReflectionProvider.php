<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Closure;
use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttributeFactory;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Broker\ConstantNotFoundException;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\RelativePathHelper;
use PHPStan\Parser\AnonymousClassVisitor;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ParamClosureThisTag;
use PHPStan\PhpDoc\Tag\ParamOutTag;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ClassNameHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\Constant\RuntimeConstantReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\Php\ExitFunctionReflection;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_key_first;
use function array_map;
use function in_array;
use function sprintf;
use function strtolower;
use const PHP_VERSION_ID;

#[NonAutowiredService(name: 'betterReflectionProvider')]
final class BetterReflectionProvider implements ReflectionProvider
{

	/** @var FunctionReflection[] */
	private array $functionReflections = [];

	/** @var ClassReflection[] */
	private array $classReflections = [];

	/** @var ClassReflection[] */
	private static array $anonymousClasses = [];

	/** @var array<string, array<string, ConstantReflection>> */
	private array $cachedConstants = [];

	/**
	 * @param list<string> $universalObjectCratesClasses
	 */
	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ClassReflectionFactory $classReflectionFactory,
		#[AutowiredParameter(ref: '@betterReflectionReflector')]
		private Reflector $reflector,
		private FileTypeMapper $fileTypeMapper,
		private DeprecationProvider $deprecationProvider,
		private PhpVersion $phpVersion,
		private NativeFunctionReflectionProvider $nativeFunctionReflectionProvider,
		private StubPhpDocProvider $stubPhpDocProvider,
		private FunctionReflectionFactory $functionReflectionFactory,
		private RelativePathHelper $relativePathHelper,
		private AnonymousClassNameHelper $anonymousClassNameHelper,
		private FileHelper $fileHelper,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		private AttributeReflectionFactory $attributeReflectionFactory,
		#[AutowiredParameter(ref: '%universalObjectCratesClasses%')]
		private array $universalObjectCratesClasses,
	)
	{
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
		} catch (IdentifierNotFound) {
			return false;
		} catch (InvalidIdentifierName) {
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
		} catch (IdentifierNotFound | InvalidIdentifierName) {
			throw new ClassNotFoundException($className);
		}

		$reflectionClassName = strtolower($reflectionClass->getName());

		if (array_key_exists($reflectionClassName, $this->classReflections)) {
			return $this->classReflections[$reflectionClassName];
		}

		if ($reflectionClass instanceof ReflectionEnum && PHP_VERSION_ID >= 80000) {
			$adaptedClass = new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum($reflectionClass);
		} else {
			$adaptedClass = new ReflectionClass($reflectionClass);
		}

		return $this->classReflections[$reflectionClassName] = $this->classReflectionFactory->create(
			$reflectionClass->getName(),
			$adaptedClass,
			null,
			null,
			$this->stubPhpDocProvider->findClassPhpDoc($reflectionClass->getName()),
		);
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

		$className = $this->anonymousClassNameHelper->getAnonymousClassName(
			$classNode,
			$scopeFile,
		);
		$classNode->name = new Node\Identifier($className);
		$classNode->namespacedName = null;

		if (isset(self::$anonymousClasses[$className])) {
			return self::$anonymousClasses[$className];
		}

		$reflectionClass = \PHPStan\BetterReflection\Reflection\ReflectionClass::createFromNode(
			$this->reflector,
			$classNode,
			new LocatedSource(FileReader::read($scopeFile), $className, $scopeFile),
			null,
		);

		$displayParentName = $reflectionClass->getParentClassName();
		if ($displayParentName === null) {
			// https://3v4l.org/6FBuP
			$classInterfaceNames = $reflectionClass->getInterfaceNames();
			if ($classInterfaceNames !== []) {
				$displayParentName = $classInterfaceNames[array_key_first($classInterfaceNames)];
			} else {
				$displayParentName = 'class';
			}
		}

		/** @var int|null $classLineIndex */
		$classLineIndex = $classNode->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX);
		$filename = $this->fileHelper->normalizePath($this->relativePathHelper->getRelativePath($scopeFile), '/');
		if ($classLineIndex === null) {
			$displayName = sprintf('%s@anonymous/%s:%s', $displayParentName, $filename, $classNode->getStartLine());
		} else {
			$displayName = sprintf('%s@anonymous/%s:%s:%d', $displayParentName, $filename, $classNode->getStartLine(), $classLineIndex);
		}

		return $this->classReflections[$className] = self::$anonymousClasses[$className] = $this->classReflectionFactory->create(
			$displayName,
			new ReflectionClass($reflectionClass),
			$scopeFile,
			null,
			$this->stubPhpDocProvider->findClassPhpDoc($className),
		);
	}

	public function getUniversalObjectCratesClasses(): array
	{
		return $this->universalObjectCratesClasses;
	}

	public function hasFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->resolveFunctionName($nameNode, $namespaceAnswerer) !== null;
	}

	public function getFunction(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): FunctionReflection
	{
		$functionName = $this->resolveFunctionName($nameNode, $namespaceAnswerer);
		if ($functionName === null) {
			throw new FunctionNotFoundException((string) $nameNode);
		}

		$lowerCasedFunctionName = strtolower($functionName);
		if (isset($this->functionReflections[$lowerCasedFunctionName])) {
			return $this->functionReflections[$lowerCasedFunctionName];
		}

		if (in_array($lowerCasedFunctionName, ['exit', 'die'], true)) {
			return $this->functionReflections[$lowerCasedFunctionName] = new ExitFunctionReflection($lowerCasedFunctionName);
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
		$phpDocParameterTypes = [];
		$phpDocReturnTag = null;
		$phpDocThrowsTag = null;

		$deprecation = $this->deprecationProvider->getFunctionDeprecation($reflectionFunction);
		$deprecationDescription = $deprecation === null ? null : $deprecation->getDescription();
		$isDeprecated = $deprecation !== null;

		$isInternal = false;
		$isPure = null;
		$asserts = Assertions::createEmpty();
		$acceptsNamedArguments = true;
		$phpDocComment = null;
		$phpDocParameterOutTags = [];
		$phpDocParameterImmediatelyInvokedCallable = [];
		$phpDocParameterClosureThisTypeTags = [];

		$resolvedPhpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($reflectionFunction->getName(), array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $reflectionFunction->getParameters()));
		if ($resolvedPhpDoc === null && $reflectionFunction->getFileName() !== false && $reflectionFunction->getDocComment() !== false) {
			$docComment = $reflectionFunction->getDocComment();
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($reflectionFunction->getFileName(), null, null, $reflectionFunction->getName(), $docComment);
		}

		if ($resolvedPhpDoc !== null) {
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
			$phpDocParameterTypes = array_map(static fn ($tag) => $tag->getType(), $resolvedPhpDoc->getParamTags());
			$phpDocReturnTag = $resolvedPhpDoc->getReturnTag();
			$phpDocThrowsTag = $resolvedPhpDoc->getThrowsTag();
			if (!$isDeprecated) {
				$deprecationDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : $deprecationDescription;
				$isDeprecated = $resolvedPhpDoc->isDeprecated();
			}
			$isInternal = $resolvedPhpDoc->isInternal();
			$isPure = $resolvedPhpDoc->isPure();
			$asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
			if ($resolvedPhpDoc->hasPhpDocString()) {
				$phpDocComment = $resolvedPhpDoc->getPhpDocString();
			}
			$acceptsNamedArguments = $resolvedPhpDoc->acceptsNamedArguments();
			$phpDocParameterOutTags = $resolvedPhpDoc->getParamOutTags();
			$phpDocParameterImmediatelyInvokedCallable = $resolvedPhpDoc->getParamsImmediatelyInvokedCallable();
			$phpDocParameterClosureThisTypeTags = $resolvedPhpDoc->getParamClosureThisTags();
		}

		return $this->functionReflectionFactory->create(
			$reflectionFunction,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnTag !== null ? $phpDocReturnTag->getType() : null,
			$phpDocThrowsTag !== null ? $phpDocThrowsTag->getType() : null,
			$deprecationDescription,
			$isDeprecated,
			$isInternal,
			$reflectionFunction->getFileName() !== false ? $reflectionFunction->getFileName() : null,
			$isPure,
			$asserts,
			$acceptsNamedArguments,
			$phpDocComment,
			array_map(static fn (ParamOutTag $paramOutTag): Type => $paramOutTag->getType(), $phpDocParameterOutTags),
			$phpDocParameterImmediatelyInvokedCallable,
			array_map(static fn (ParamClosureThisTag $tag): Type => $tag->getType(), $phpDocParameterClosureThisTypeTags),
			$this->attributeReflectionFactory->fromNativeReflection($reflectionFunction->getAttributes(), InitializerExprContext::fromFunction($reflectionFunction->getName(), $reflectionFunction->getFileName() !== false ? $reflectionFunction->getFileName() : null)),
		);
	}

	public function resolveFunctionName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		$name = $nameNode->toLowerString();
		if (in_array($name, ['exit', 'die'], true)) {
			return $name;
		}

		return $this->resolveName($nameNode, function (string $name): bool {
			try {
				$this->reflector->reflectFunction($name);
				return true;
			} catch (IdentifierNotFound) {
				// pass
			} catch (InvalidIdentifierName) {
				// pass
			}

			if ($this->nativeFunctionReflectionProvider->findFunctionReflection($name) !== null) {
				return $this->phpstormStubsSourceStubber->isPresentFunction($name) !== false;
			}
			return false;
		}, $namespaceAnswerer);
	}

	public function hasConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): bool
	{
		return $this->resolveConstantName($nameNode, $namespaceAnswerer) !== null;
	}

	public function getConstant(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ConstantReflection
	{
		$constantName = $this->resolveConstantName($nameNode, $namespaceAnswerer);
		if ($constantName === null) {
			throw new ConstantNotFoundException((string) $nameNode);
		}

		$phpVersionType = null;
		$versionKey = 'current_version';
		if ($namespaceAnswerer instanceof Scope) {
			$phpVersionType = $namespaceAnswerer->getPhpVersion()->getType();
			$versionKey = $phpVersionType->describe(VerbosityLevel::cache());
		}

		if (!array_key_exists($versionKey, $this->cachedConstants)) {
			$this->cachedConstants[$versionKey] = [];
		}

		if (array_key_exists($constantName, $this->cachedConstants[$versionKey])) {
			return $this->cachedConstants[$versionKey][$constantName];
		}

		$constantReflection = $this->reflector->reflectConstant($constantName);
		$fileName = $constantReflection->getFileName();
		$constantValueType = $this->initializerExprTypeResolver->getType($constantReflection->getValueExpression(), InitializerExprContext::fromGlobalConstant($constantReflection));
		$docComment = $constantReflection->getDocComment();

		$deprecation = $this->deprecationProvider->getConstantDeprecation($constantReflection);
		$isDeprecated = $deprecation !== null;
		$deprecatedDescription = $deprecation === null ? null : $deprecation->getDescription();

		if ($isDeprecated === false && $docComment !== null) {
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, null, $docComment);
			$isDeprecated = $resolvedPhpDoc->isDeprecated();

			if ($isDeprecated && $resolvedPhpDoc->getDeprecatedTag() !== null) {
				$deprecatedMessage = $resolvedPhpDoc->getDeprecatedTag()->getMessage();

				$matches = Strings::match($deprecatedMessage ?? '', '#^(\d+)\.(\d+)(?:\.(\d+))?$#');
				if ($matches !== null) {
					$major = (int) $matches[1];
					$minor = (int) $matches[2];
					$patch = (int) ($matches[3] ?? 0);
					$versionId = sprintf('%d%02d%02d', $major, $minor, $patch);

					if ($phpVersionType !== null) {
						$isDeprecated = IntegerRangeType::fromInterval((int) $versionId, null)->isSuperTypeOf($phpVersionType)->yes();
					} else {
						$isDeprecated = $this->phpVersion->getVersionId() >= $versionId;
					}
				} else {
					// filter raw version number messages like in
					// https://github.com/JetBrains/phpstorm-stubs/blob/9608c953230b08f07b703ecfe459cc58d5421437/filter/filter.php#L478
					$deprecatedDescription = $deprecatedMessage;
				}
			} elseif (!$isDeprecated) {
				$isDeprecated = $constantReflection->isDeprecated();
			}
		} elseif (!$isDeprecated) {
			$isDeprecated = $constantReflection->isDeprecated();
		}

		return $this->cachedConstants[$versionKey][$constantName] = new RuntimeConstantReflection(
			$constantName,
			$constantValueType,
			$fileName,
			TrinaryLogic::createFromBoolean($isDeprecated),
			$deprecatedDescription,
			$this->attributeReflectionFactory->fromNativeReflection(
				array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute) => ReflectionAttributeFactory::create($betterReflectionAttribute), $constantReflection->getAttributes()),
				InitializerExprContext::fromGlobalConstant($constantReflection),
			),
			$constantReflection->isInternal(),
		);
	}

	public function resolveConstantName(Node\Name $nameNode, ?NamespaceAnswerer $namespaceAnswerer): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			try {
				$this->reflector->reflectConstant($name);
				return true;
			} catch (IdentifierNotFound) {
				// pass
			} catch (InvalidIdentifierName) {
				// pass
			} catch (UnableToCompileNode) {
				// pass
			}
			return false;
		}, $namespaceAnswerer);
	}

	/**
	 * @param Closure(string $name): bool $existsCallback
	 */
	private function resolveName(
		Node\Name $nameNode,
		Closure $existsCallback,
		?NamespaceAnswerer $namespaceAnswerer,
	): ?string
	{
		$name = (string) $nameNode;
		if ($namespaceAnswerer !== null && $namespaceAnswerer->getNamespace() !== null && !$nameNode->isFullyQualified()) {
			$namespacedName = sprintf('%s\\%s', $namespaceAnswerer->getNamespace(), $name);
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
