<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Attribute;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\PhpDoc\Tag\RequireImplementsTag;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\Tag\TypeAliasImportTag;
use PHPStan\PhpDoc\Tag\TypeAliasTag;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsMethodsClassReflectionExtension;
use PHPStan\Reflection\RequireExtension\RequireExtendsPropertiesClassReflectionExtension;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\CircularTypeAliasDefinitionException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Generic\TypeProjectionHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeAlias;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VerbosityLevel;
use ReflectionException;
use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_bool;
use function is_file;
use function is_int;
use function reset;
use function sprintf;
use function strtolower;

/**
 * @api
 * @final
 */
class ClassReflection
{

	/** @var ExtendedMethodReflection[] */
	private array $methods = [];

	/** @var ExtendedPropertyReflection[] */
	private array $properties = [];

	/** @var ClassConstantReflection[] */
	private array $constants = [];

	/** @var EnumCaseReflection[]|null  */
	private ?array $enumCases = null;

	/** @var int[]|null */
	private ?array $classHierarchyDistances = null;

	private ?string $deprecatedDescription = null;

	private ?bool $isDeprecated = null;

	private ?bool $isGeneric = null;

	private ?bool $isInternal = null;

	private ?bool $isFinal = null;

	private ?bool $isImmutable = null;

	private ?bool $hasConsistentConstructor = null;

	private ?bool $acceptsNamedArguments = null;

	private ?TemplateTypeMap $templateTypeMap = null;

	private ?TemplateTypeMap $activeTemplateTypeMap = null;

	private ?TemplateTypeVarianceMap $defaultCallSiteVarianceMap = null;

	private ?TemplateTypeVarianceMap $callSiteVarianceMap = null;

	/** @var array<string,ClassReflection>|null */
	private ?array $ancestors = null;

	private ?string $cacheKey = null;

	/** @var array<string, bool> */
	private array $subclasses = [];

	private string|false|null $filename = false;

	private string|false|null $reflectionDocComment = false;

	private false|ResolvedPhpDocBlock $resolvedPhpDocBlock = false;

	private false|ResolvedPhpDocBlock $traitContextResolvedPhpDocBlock = false;

	/** @var ClassReflection[]|null */
	private ?array $cachedInterfaces = null;

	private ClassReflection|false|null $cachedParentClass = false;

	/** @var array<string, TypeAlias>|null */
	private ?array $typeAliases = null;

	/** @var array<string, true> */
	private static array $resolvingTypeAliasImports = [];

	/**
	 * @param PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
	 * @param MethodsClassReflectionExtension[] $methodsClassReflectionExtensions
	 * @param AllowedSubTypesClassReflectionExtension[] $allowedSubTypesClassReflectionExtensions
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private FileTypeMapper $fileTypeMapper,
		private StubPhpDocProvider $stubPhpDocProvider,
		private PhpDocInheritanceResolver $phpDocInheritanceResolver,
		private PhpVersion $phpVersion,
		private SignatureMapProvider $signatureMapProvider,
		private array $propertiesClassReflectionExtensions,
		private array $methodsClassReflectionExtensions,
		private array $allowedSubTypesClassReflectionExtensions,
		private RequireExtendsPropertiesClassReflectionExtension $requireExtendsPropertiesClassReflectionExtension,
		private RequireExtendsMethodsClassReflectionExtension $requireExtendsMethodsClassReflectionExtension,
		private string $displayName,
		private ReflectionClass|ReflectionEnum $reflection,
		private ?string $anonymousFilename,
		private ?TemplateTypeMap $resolvedTemplateTypeMap,
		private ?ResolvedPhpDocBlock $stubPhpDocBlock,
		private array $universalObjectCratesClasses,
		private ?string $extraCacheKey = null,
		private ?TemplateTypeVarianceMap $resolvedCallSiteVarianceMap = null,
	)
	{
	}

	public function getNativeReflection(): ReflectionClass|ReflectionEnum
	{
		return $this->reflection;
	}

	public function getFileName(): ?string
	{
		if (!is_bool($this->filename)) {
			return $this->filename;
		}

		if ($this->anonymousFilename !== null) {
			return $this->filename = $this->anonymousFilename;
		}
		$fileName = $this->reflection->getFileName();
		if ($fileName === false) {
			return $this->filename = null;
		}

		if (!is_file($fileName)) {
			return $this->filename = null;
		}

		return $this->filename = $fileName;
	}

	/**
	 * @deprecated Use getFileName()
	 */
	public function getFileNameWithPhpDocs(): ?string
	{
		return $this->getFileName();
	}

	public function getParentClass(): ?ClassReflection
	{
		if (!is_bool($this->cachedParentClass)) {
			return $this->cachedParentClass;
		}

		$parentClass = $this->reflection->getParentClass();

		if ($parentClass === false) {
			return $this->cachedParentClass = null;
		}

		$extendsTag = $this->getFirstExtendsTag();

		if ($extendsTag !== null && $this->isValidAncestorType($extendsTag->getType(), [$parentClass->getName()])) {
			$extendedType = $extendsTag->getType();

			if ($this->isGeneric()) {
				$extendedType = TemplateTypeHelper::resolveTemplateTypes(
					$extendedType,
					$this->getPossiblyIncompleteActiveTemplateTypeMap(),
					$this->getCallSiteVarianceMap(),
					TemplateTypeVariance::createStatic(),
				);
			}

			if (!$extendedType instanceof GenericObjectType) {
				return $this->reflectionProvider->getClass($parentClass->getName());
			}

			return $extendedType->getClassReflection() ?? $this->reflectionProvider->getClass($parentClass->getName());
		}

		$parentReflection = $this->reflectionProvider->getClass($parentClass->getName());
		if ($parentReflection->isGeneric()) {
			return $parentReflection->withTypes(
				array_values($parentReflection->getTemplateTypeMap()->map(static fn (): Type => new ErrorType())->getTypes()),
			);
		}

		$this->cachedParentClass = $parentReflection;

		return $parentReflection;
	}

	/**
	 * @return class-string
	 */
	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getDisplayName(bool $withTemplateTypes = true): string
	{
		if (
			$withTemplateTypes === false
			|| $this->resolvedTemplateTypeMap === null
			|| count($this->resolvedTemplateTypeMap->getTypes()) === 0
		) {
			return $this->displayName;
		}

		$templateTypes = [];
		$variances = $this->getCallSiteVarianceMap()->getVariances();
		foreach ($this->getActiveTemplateTypeMap()->getTypes() as $name => $templateType) {
			$variance = $variances[$name] ?? null;
			if ($variance === null) {
				continue;
			}

			$templateTypes[] = TypeProjectionHelper::describe($templateType, $variance, VerbosityLevel::typeOnly());
		}

		return $this->displayName . '<' . implode(',', $templateTypes) . '>';
	}

	public function getCacheKey(): string
	{
		$cacheKey = $this->cacheKey;
		if ($cacheKey !== null) {
			return $this->cacheKey;
		}

		$cacheKey = $this->displayName;

		if ($this->resolvedTemplateTypeMap !== null) {
			$templateTypes = [];
			$variances = $this->getCallSiteVarianceMap()->getVariances();
			foreach ($this->getActiveTemplateTypeMap()->getTypes() as $name => $templateType) {
				$variance = $variances[$name] ?? null;
				if ($variance === null) {
					continue;
				}

				$templateTypes[] = TypeProjectionHelper::describe($templateType, $variance, VerbosityLevel::cache());
			}

			$cacheKey .= '<' . implode(',', $templateTypes) . '>';
		}

		if ($this->extraCacheKey !== null) {
			$cacheKey .= '-' . $this->extraCacheKey;
		}

		$this->cacheKey = $cacheKey;

		return $cacheKey;
	}

	/**
	 * @return int[]
	 */
	public function getClassHierarchyDistances(): array
	{
		if ($this->classHierarchyDistances === null) {
			$distance = 0;
			$distances = [
				$this->getName() => $distance,
			];
			$currentClassReflection = $this->getNativeReflection();
			foreach ($this->collectTraits($this->getNativeReflection()) as $trait) {
				$distance++;
				if (array_key_exists($trait->getName(), $distances)) {
					continue;
				}

				$distances[$trait->getName()] = $distance;
			}

			while ($currentClassReflection->getParentClass() !== false) {
				$distance++;
				$parentClassName = $currentClassReflection->getParentClass()->getName();
				if (!array_key_exists($parentClassName, $distances)) {
					$distances[$parentClassName] = $distance;
				}
				$currentClassReflection = $currentClassReflection->getParentClass();
				foreach ($this->collectTraits($currentClassReflection) as $trait) {
					$distance++;
					if (array_key_exists($trait->getName(), $distances)) {
						continue;
					}

					$distances[$trait->getName()] = $distance;
				}
			}
			foreach ($this->getNativeReflection()->getInterfaces() as $interface) {
				$distance++;
				if (array_key_exists($interface->getName(), $distances)) {
					continue;
				}

				$distances[$interface->getName()] = $distance;
			}

			$this->classHierarchyDistances = $distances;
		}

		return $this->classHierarchyDistances;
	}

	/**
	 * @return ReflectionClass[]
	 */
	private function collectTraits(ReflectionClass|ReflectionEnum $class): array
	{
		$traits = [];
		$traitsLeftToAnalyze = $class->getTraits();

		while (count($traitsLeftToAnalyze) !== 0) {
			$trait = reset($traitsLeftToAnalyze);
			$traits[] = $trait;

			foreach ($trait->getTraits() as $subTrait) {
				if (in_array($subTrait, $traits, true)) {
					continue;
				}

				$traitsLeftToAnalyze[] = $subTrait;
			}

			array_shift($traitsLeftToAnalyze);
		}

		return $traits;
	}

	public function allowsDynamicProperties(): bool
	{
		if ($this->isEnum()) {
			return false;
		}

		if (!$this->phpVersion->deprecatesDynamicProperties()) {
			return true;
		}

		if ($this->isReadOnly()) {
			return false;
		}

		if (UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
			$this->reflectionProvider,
			$this->universalObjectCratesClasses,
			$this,
		)) {
			return true;
		}

		$class = $this;
		$attributes = $class->reflection->getAttributes('AllowDynamicProperties');
		while (count($attributes) === 0 && $class->getParentClass() !== null) {
			$attributes = $class->getParentClass()->reflection->getAttributes('AllowDynamicProperties');
			$class = $class->getParentClass();
		}

		return count($attributes) > 0;
	}

	private function allowsDynamicPropertiesExtensions(): bool
	{
		if ($this->allowsDynamicProperties()) {
			return true;
		}

		$hasMagicMethod = $this->hasNativeMethod('__get') || $this->hasNativeMethod('__set') || $this->hasNativeMethod('__isset');
		if ($hasMagicMethod) {
			return true;
		}

		foreach ($this->getRequireExtendsTags() as $extendsTag) {
			$type = $extendsTag->getType();
			if (!$type instanceof ObjectType) {
				continue;
			}

			$reflection = $type->getClassReflection();
			if ($reflection === null) {
				continue;
			}

			if (!$reflection->allowsDynamicPropertiesExtensions()) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function hasProperty(string $propertyName): bool
	{
		if ($this->isEnum()) {
			return $this->hasNativeProperty($propertyName);
		}

		foreach ($this->propertiesClassReflectionExtensions as $i => $extension) {
			if ($i > 0 && !$this->allowsDynamicPropertiesExtensions()) {
				break;
			}
			if ($extension->hasProperty($this, $propertyName)) {
				return true;
			}
		}

		if ($this->requireExtendsPropertiesClassReflectionExtension->hasProperty($this, $propertyName)) {
			return true;
		}

		return false;
	}

	public function hasMethod(string $methodName): bool
	{
		foreach ($this->methodsClassReflectionExtensions as $extension) {
			if ($extension->hasMethod($this, $methodName)) {
				return true;
			}
		}

		if ($this->requireExtendsMethodsClassReflectionExtension->hasMethod($this, $methodName)) {
			return true;
		}

		return false;
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		$key = $methodName;
		if ($scope->isInClass()) {
			$key = sprintf('%s-%s', $key, $scope->getClassReflection()->getCacheKey());
		}

		if (!isset($this->methods[$key])) {
			foreach ($this->methodsClassReflectionExtensions as $extension) {
				if (!$extension->hasMethod($this, $methodName)) {
					continue;
				}

				$method = $this->wrapExtendedMethod($extension->getMethod($this, $methodName));
				if ($scope->canCallMethod($method)) {
					return $this->methods[$key] = $method;
				}
				$this->methods[$key] = $method;
			}
		}

		if (!isset($this->methods[$key])) {
			if ($this->requireExtendsMethodsClassReflectionExtension->hasMethod($this, $methodName)) {
				$method = $this->requireExtendsMethodsClassReflectionExtension->getMethod($this, $methodName);
				$this->methods[$key] = $method;
			}
		}

		if (!isset($this->methods[$key])) {
			throw new MissingMethodFromReflectionException($this->getName(), $methodName);
		}

		return $this->methods[$key];
	}

	private function wrapExtendedMethod(MethodReflection $method): ExtendedMethodReflection
	{
		if ($method instanceof ExtendedMethodReflection) {
			return $method;
		}

		return new WrappedExtendedMethodReflection($method);
	}

	private function wrapExtendedProperty(PropertyReflection $method): ExtendedPropertyReflection
	{
		if ($method instanceof ExtendedPropertyReflection) {
			return $method;
		}

		return new WrappedExtendedPropertyReflection($method);
	}

	public function hasNativeMethod(string $methodName): bool
	{
		return $this->getPhpExtension()->hasNativeMethod($this, $methodName);
	}

	public function getNativeMethod(string $methodName): ExtendedMethodReflection
	{
		if (!$this->hasNativeMethod($methodName)) {
			throw new MissingMethodFromReflectionException($this->getName(), $methodName);
		}
		return $this->getPhpExtension()->getNativeMethod($this, $methodName);
	}

	public function hasConstructor(): bool
	{
		return $this->findConstructor() !== null;
	}

	public function getConstructor(): ExtendedMethodReflection
	{
		$constructor = $this->findConstructor();
		if ($constructor === null) {
			throw new ShouldNotHappenException();
		}
		return $this->getNativeMethod($constructor->getName());
	}

	private function findConstructor(): ?ReflectionMethod
	{
		$constructor = $this->reflection->getConstructor();
		if ($constructor === null) {
			return null;
		}

		if ($this->phpVersion->supportsLegacyConstructor()) {
			return $constructor;
		}

		if (strtolower($constructor->getName()) !== '__construct') {
			return null;
		}

		return $constructor;
	}

	private function getPhpExtension(): PhpClassReflectionExtension
	{
		$extension = $this->methodsClassReflectionExtensions[0];
		if (!$extension instanceof PhpClassReflectionExtension) {
			throw new ShouldNotHappenException();
		}

		return $extension;
	}

	/** @internal */
	public function evictPrivateSymbols(): void
	{
		foreach ($this->constants as $name => $constant) {
			if (!$constant->isPrivate()) {
				continue;
			}

			unset($this->constants[$name]);
		}
		foreach ($this->properties as $name => $property) {
			if (!$property->isPrivate()) {
				continue;
			}

			unset($this->properties[$name]);
		}
		foreach ($this->methods as $name => $method) {
			if (!$method->isPrivate()) {
				continue;
			}

			unset($this->methods[$name]);
		}
		$this->getPhpExtension()->evictPrivateSymbols($this->getCacheKey());
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		if ($this->isEnum()) {
			return $this->getNativeProperty($propertyName);
		}

		$key = $propertyName;
		if ($scope->isInClass()) {
			$key = sprintf('%s-%s', $key, $scope->getClassReflection()->getCacheKey());
		}

		if (!isset($this->properties[$key])) {
			foreach ($this->propertiesClassReflectionExtensions as $i => $extension) {
				if ($i > 0 && !$this->allowsDynamicPropertiesExtensions()) {
					break;
				}

				if (!$extension->hasProperty($this, $propertyName)) {
					continue;
				}

				$property = $this->wrapExtendedProperty($extension->getProperty($this, $propertyName));
				if ($scope->canAccessProperty($property)) {
					return $this->properties[$key] = $property;
				}
				$this->properties[$key] = $property;
			}
		}

		if (!isset($this->properties[$key])) {
			if ($this->requireExtendsPropertiesClassReflectionExtension->hasProperty($this, $propertyName)) {
				$property = $this->requireExtendsPropertiesClassReflectionExtension->getProperty($this, $propertyName);
				$this->properties[$key] = $property;
			}
		}

		if (!isset($this->properties[$key])) {
			throw new MissingPropertyFromReflectionException($this->getName(), $propertyName);
		}

		return $this->properties[$key];
	}

	public function hasNativeProperty(string $propertyName): bool
	{
		return $this->getPhpExtension()->hasProperty($this, $propertyName);
	}

	public function getNativeProperty(string $propertyName): PhpPropertyReflection
	{
		if (!$this->hasNativeProperty($propertyName)) {
			throw new MissingPropertyFromReflectionException($this->getName(), $propertyName);
		}

		return $this->getPhpExtension()->getNativeProperty($this, $propertyName);
	}

	public function isAbstract(): bool
	{
		return $this->reflection->isAbstract();
	}

	public function isInterface(): bool
	{
		return $this->reflection->isInterface();
	}

	public function isTrait(): bool
	{
		return $this->reflection->isTrait();
	}

	/**
	 * @phpstan-assert-if-true ReflectionEnum $this->reflection
	 */
	public function isEnum(): bool
	{
		return $this->reflection instanceof ReflectionEnum && $this->reflection->isEnum();
	}

	/**
	 * @return 'Interface'|'Trait'|'Enum'|'Class'
	 */
	public function getClassTypeDescription(): string
	{
		if ($this->isInterface()) {
			return 'Interface';
		} elseif ($this->isTrait()) {
			return 'Trait';
		} elseif ($this->isEnum()) {
			return 'Enum';
		}

		return 'Class';
	}

	public function isReadOnly(): bool
	{
		return $this->reflection->isReadOnly();
	}

	public function isBackedEnum(): bool
	{
		if (!$this->reflection instanceof ReflectionEnum) {
			return false;
		}

		return $this->reflection->isBacked();
	}

	public function getBackedEnumType(): ?Type
	{
		if (!$this->reflection instanceof ReflectionEnum) {
			return null;
		}

		if (!$this->reflection->isBacked()) {
			return null;
		}

		return TypehintHelper::decideTypeFromReflection($this->reflection->getBackingType());
	}

	public function hasEnumCase(string $name): bool
	{
		if (!$this->isEnum()) {
			return false;
		}

		return $this->reflection->hasCase($name);
	}

	/**
	 * @return array<string, EnumCaseReflection>
	 */
	public function getEnumCases(): array
	{
		if (!$this->isEnum()) {
			throw new ShouldNotHappenException();
		}

		if ($this->enumCases !== null) {
			return $this->enumCases;
		}

		$cases = [];
		$initializerExprContext = InitializerExprContext::fromClassReflection($this);
		foreach ($this->reflection->getCases() as $case) {
			$valueType = null;
			if ($case instanceof ReflectionEnumBackedCase) {
				$valueType = $this->initializerExprTypeResolver->getType($case->getValueExpression(), $initializerExprContext);
			}
			/** @var string $caseName */
			$caseName = $case->getName();
			$cases[$caseName] = new EnumCaseReflection($this, $caseName, $valueType);
		}

		return $this->enumCases = $cases;
	}

	public function getEnumCase(string $name): EnumCaseReflection
	{
		if (!$this->hasEnumCase($name)) {
			throw new ShouldNotHappenException(sprintf('Enum case %s::%s does not exist.', $this->getDisplayName(), $name));
		}

		if (!$this->reflection instanceof ReflectionEnum) {
			throw new ShouldNotHappenException();
		}

		if ($this->enumCases !== null && array_key_exists($name, $this->enumCases)) {
			return $this->enumCases[$name];
		}

		$case = $this->reflection->getCase($name);
		$valueType = null;
		if ($case instanceof ReflectionEnumBackedCase) {
			$valueType = $this->initializerExprTypeResolver->getType($case->getValueExpression(), InitializerExprContext::fromClassReflection($this));
		}

		return new EnumCaseReflection($this, $name, $valueType);
	}

	public function isClass(): bool
	{
		return !$this->isInterface() && !$this->isTrait() && !$this->isEnum();
	}

	public function isAnonymous(): bool
	{
		return $this->anonymousFilename !== null;
	}

	public function is(string $className): bool
	{
		return $this->getName() === $className || $this->isSubclassOf($className);
	}

	public function isSubclassOf(string $className): bool
	{
		if (isset($this->subclasses[$className])) {
			return $this->subclasses[$className];
		}

		if (!$this->reflectionProvider->hasClass($className)) {
			return $this->subclasses[$className] = false;
		}

		try {
			return $this->subclasses[$className] = $this->reflection->isSubclassOf($className);
		} catch (ReflectionException) {
			return $this->subclasses[$className] = false;
		}
	}

	public function implementsInterface(string $className): bool
	{
		try {
			return $this->reflection->implementsInterface($className);
		} catch (ReflectionException) {
			return false;
		}
	}

	/**
	 * @return ClassReflection[]
	 */
	public function getParents(): array
	{
		$parents = [];
		$parent = $this->getParentClass();
		while ($parent !== null) {
			$parents[] = $parent;
			$parent = $parent->getParentClass();
		}

		return $parents;
	}

	/**
	 * @return ClassReflection[]
	 */
	public function getInterfaces(): array
	{
		if ($this->cachedInterfaces !== null) {
			return $this->cachedInterfaces;
		}

		$interfaces = $this->getImmediateInterfaces();
		$immediateInterfaces = $interfaces;
		$parent = $this->getParentClass();
		while ($parent !== null) {
			foreach ($parent->getImmediateInterfaces() as $parentInterface) {
				$interfaces[$parentInterface->getName()] = $parentInterface;
				foreach ($this->collectInterfaces($parentInterface) as $parentInterfaceInterface) {
					$interfaces[$parentInterfaceInterface->getName()] = $parentInterfaceInterface;
				}
			}

			$parent = $parent->getParentClass();
		}

		foreach ($immediateInterfaces as $immediateInterface) {
			foreach ($this->collectInterfaces($immediateInterface) as $interfaceInterface) {
				$interfaces[$interfaceInterface->getName()] = $interfaceInterface;
			}
		}

		$this->cachedInterfaces = $interfaces;

		return $interfaces;
	}

	/**
	 * @return ClassReflection[]
	 */
	private function collectInterfaces(ClassReflection $interface): array
	{
		$interfaces = [];
		foreach ($interface->getImmediateInterfaces() as $immediateInterface) {
			$interfaces[$immediateInterface->getName()] = $immediateInterface;
			foreach ($this->collectInterfaces($immediateInterface) as $immediateInterfaceInterface) {
				$interfaces[$immediateInterfaceInterface->getName()] = $immediateInterfaceInterface;
			}
		}

		return $interfaces;
	}

	/**
	 * @return ClassReflection[]
	 */
	public function getImmediateInterfaces(): array
	{
		$indirectInterfaceNames = [];
		$parent = $this->getParentClass();
		while ($parent !== null) {
			foreach ($parent->getNativeReflection()->getInterfaceNames() as $parentInterfaceName) {
				$indirectInterfaceNames[] = $parentInterfaceName;
			}

			$parent = $parent->getParentClass();
		}

		foreach ($this->getNativeReflection()->getInterfaces() as $interfaceInterface) {
			foreach ($interfaceInterface->getInterfaceNames() as $interfaceInterfaceName) {
				$indirectInterfaceNames[] = $interfaceInterfaceName;
			}
		}

		if ($this->reflection->isInterface()) {
			$implementsTags = $this->getExtendsTags();
		} else {
			$implementsTags = $this->getImplementsTags();
		}

		$immediateInterfaceNames = array_diff($this->getNativeReflection()->getInterfaceNames(), $indirectInterfaceNames);
		$immediateInterfaces = [];
		foreach ($immediateInterfaceNames as $immediateInterfaceName) {
			if (!$this->reflectionProvider->hasClass($immediateInterfaceName)) {
				continue;
			}

			$immediateInterface = $this->reflectionProvider->getClass($immediateInterfaceName);
			if (array_key_exists($immediateInterface->getName(), $implementsTags)) {
				$implementsTag = $implementsTags[$immediateInterface->getName()];
				$implementedType = $implementsTag->getType();
				if ($this->isGeneric()) {
					$implementedType = TemplateTypeHelper::resolveTemplateTypes(
						$implementedType,
						$this->getPossiblyIncompleteActiveTemplateTypeMap(),
						$this->getCallSiteVarianceMap(),
						TemplateTypeVariance::createStatic(),
						true,
					);
				}

				if (
					$implementedType instanceof GenericObjectType
					&& $implementedType->getClassReflection() !== null
				) {
					$immediateInterfaces[$immediateInterface->getName()] = $implementedType->getClassReflection();
					continue;
				}
			}

			if ($immediateInterface->isGeneric()) {
				$immediateInterfaces[$immediateInterface->getName()] = $immediateInterface->withTypes(
					array_values($immediateInterface->getTemplateTypeMap()->map(static fn (): Type => new ErrorType())->getTypes()),
				);
				continue;
			}

			$immediateInterfaces[$immediateInterface->getName()] = $immediateInterface;
		}

		return $immediateInterfaces;
	}

	/**
	 * @return array<string, ClassReflection>
	 */
	public function getTraits(bool $recursive = false): array
	{
		$traits = [];

		if ($recursive) {
			foreach ($this->collectTraits($this->getNativeReflection()) as $trait) {
				$traits[$trait->getName()] = $trait;
			}
		} else {
			$traits = $this->getNativeReflection()->getTraits();
		}

		$traits = array_map(fn (ReflectionClass $trait): ClassReflection => $this->reflectionProvider->getClass($trait->getName()), $traits);

		if ($recursive) {
			$parentClass = $this->getNativeReflection()->getParentClass();

			if ($parentClass !== false) {
				return array_merge(
					$traits,
					$this->reflectionProvider->getClass($parentClass->getName())->getTraits(true),
				);
			}
		}

		return $traits;
	}

	/**
	 * @return list<class-string>
	 */
	public function getParentClassesNames(): array
	{
		$parentNames = [];
		$parentClass = $this->getParentClass();
		while ($parentClass !== null) {
			$parentNames[] = $parentClass->getName();
			$parentClass = $parentClass->getParentClass();
		}

		return $parentNames;
	}

	public function hasConstant(string $name): bool
	{
		if (!$this->getNativeReflection()->hasConstant($name)) {
			return false;
		}

		$reflectionConstant = $this->getNativeReflection()->getReflectionConstant($name);
		if ($reflectionConstant === false) {
			return false;
		}

		return $this->reflectionProvider->hasClass($reflectionConstant->getDeclaringClass()->getName());
	}

	public function getConstant(string $name): ClassConstantReflection
	{
		if (!isset($this->constants[$name])) {
			$reflectionConstant = $this->getNativeReflection()->getReflectionConstant($name);
			if ($reflectionConstant === false) {
				throw new MissingConstantFromReflectionException($this->getName(), $name);
			}

			$declaringClass = $this->reflectionProvider->getClass($reflectionConstant->getDeclaringClass()->getName());
			$fileName = $declaringClass->getFileName();
			$phpDocType = null;
			$resolvedPhpDoc = $this->stubPhpDocProvider->findClassConstantPhpDoc(
				$declaringClass->getName(),
				$name,
			);
			if ($resolvedPhpDoc === null) {
				$docComment = null;
				if ($reflectionConstant->getDocComment() !== false) {
					$docComment = $reflectionConstant->getDocComment();
				}
				$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForConstant(
					$docComment,
					$declaringClass,
					$fileName,
					$name,
				);
			}

			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$varTags = $resolvedPhpDoc->getVarTags();
			if (isset($varTags[0]) && count($varTags) === 1) {
				$phpDocType = $varTags[0]->getType();
			}

			$nativeType = null;
			if ($reflectionConstant->getType() !== null) {
				$nativeType = TypehintHelper::decideTypeFromReflection($reflectionConstant->getType(), null, $declaringClass);
			} elseif ($this->signatureMapProvider->hasClassConstantMetadata($declaringClass->getName(), $name)) {
				$nativeType = $this->signatureMapProvider->getClassConstantMetadata($declaringClass->getName(), $name)['nativeType'];
			}

			$this->constants[$name] = new ClassConstantReflection(
				$this->initializerExprTypeResolver,
				$declaringClass,
				$reflectionConstant,
				$nativeType,
				$phpDocType,
				$deprecatedDescription,
				$isDeprecated,
				$isInternal,
			);
		}
		return $this->constants[$name];
	}

	public function hasTraitUse(string $traitName): bool
	{
		return in_array($traitName, $this->getTraitNames(), true);
	}

	/**
	 * @return string[]
	 */
	private function getTraitNames(): array
	{
		$class = $this->reflection;
		$traitNames = array_map(static fn (ReflectionClass $class) => $class->getName(), $this->collectTraits($class));
		while ($class->getParentClass() !== false) {
			$traitNames = array_values(array_unique(array_merge($traitNames, $class->getParentClass()->getTraitNames())));
			$class = $class->getParentClass();
		}

		return $traitNames;
	}

	/**
	 * @return array<string, TypeAlias>
	 */
	public function getTypeAliases(): array
	{
		if ($this->typeAliases === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			if ($resolvedPhpDoc === null) {
				return $this->typeAliases = [];
			}

			$typeAliasImportTags = $resolvedPhpDoc->getTypeAliasImportTags();
			$typeAliasTags = $resolvedPhpDoc->getTypeAliasTags();

			// prevent circular imports
			if (array_key_exists($this->getName(), self::$resolvingTypeAliasImports)) {
				throw new CircularTypeAliasDefinitionException();
			}

			self::$resolvingTypeAliasImports[$this->getName()] = true;

			$importedAliases = array_map(function (TypeAliasImportTag $typeAliasImportTag): ?TypeAlias {
				$importedAlias = $typeAliasImportTag->getImportedAlias();
				$importedFromClassName = $typeAliasImportTag->getImportedFrom();

				if (!$this->reflectionProvider->hasClass($importedFromClassName)) {
					return null;
				}

				$importedFromReflection = $this->reflectionProvider->getClass($importedFromClassName);

				try {
					$typeAliases = $importedFromReflection->getTypeAliases();
				} catch (CircularTypeAliasDefinitionException) {
					return TypeAlias::invalid();
				}

				if (!array_key_exists($importedAlias, $typeAliases)) {
					return null;
				}

				return $typeAliases[$importedAlias];
			}, $typeAliasImportTags);

			unset(self::$resolvingTypeAliasImports[$this->getName()]);

			$localAliases = array_map(static fn (TypeAliasTag $typeAliasTag): TypeAlias => $typeAliasTag->getTypeAlias(), $typeAliasTags);

			$this->typeAliases = array_filter(
				array_merge($importedAliases, $localAliases),
				static fn (?TypeAlias $typeAlias): bool => $typeAlias !== null,
			);
		}

		return $this->typeAliases;
	}

	public function getDeprecatedDescription(): ?string
	{
		if ($this->deprecatedDescription === null && $this->isDeprecated()) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			if ($resolvedPhpDoc !== null && $resolvedPhpDoc->getDeprecatedTag() !== null) {
				$this->deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag()->getMessage();
			}
		}

		return $this->deprecatedDescription;
	}

	public function isDeprecated(): bool
	{
		if ($this->isDeprecated === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			$this->isDeprecated = $resolvedPhpDoc !== null && $resolvedPhpDoc->isDeprecated();
		}

		return $this->isDeprecated;
	}

	public function isBuiltin(): bool
	{
		return $this->reflection->isInternal();
	}

	public function isInternal(): bool
	{
		if ($this->isInternal === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			$this->isInternal = $resolvedPhpDoc !== null && $resolvedPhpDoc->isInternal();
		}

		return $this->isInternal;
	}

	public function isFinal(): bool
	{
		if ($this->isFinalByKeyword()) {
			return true;
		}

		if ($this->isFinal === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			$this->isFinal = $resolvedPhpDoc !== null && $resolvedPhpDoc->isFinal();
		}

		return $this->isFinal;
	}

	public function isImmutable(): bool
	{
		if ($this->isImmutable === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			$this->isImmutable = $resolvedPhpDoc !== null && $resolvedPhpDoc->isImmutable();

			$parentClass = $this->getParentClass();
			if ($parentClass !== null && !$this->isImmutable) {
				$this->isImmutable = $parentClass->isImmutable();
			}
		}

		return $this->isImmutable;
	}

	public function hasConsistentConstructor(): bool
	{
		if ($this->hasConsistentConstructor === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			$this->hasConsistentConstructor = $resolvedPhpDoc !== null && $resolvedPhpDoc->hasConsistentConstructor();
		}

		return $this->hasConsistentConstructor;
	}

	public function acceptsNamedArguments(): bool
	{
		if ($this->acceptsNamedArguments === null) {
			$resolvedPhpDoc = $this->getResolvedPhpDoc();
			$this->acceptsNamedArguments = $resolvedPhpDoc === null || $resolvedPhpDoc->acceptsNamedArguments();
		}

		return $this->acceptsNamedArguments;
	}

	public function isFinalByKeyword(): bool
	{
		if ($this->isAnonymous()) {
			return true;
		}

		return $this->reflection->isFinal();
	}

	public function isAttributeClass(): bool
	{
		return $this->findAttributeFlags() !== null;
	}

	private function findAttributeFlags(): ?int
	{
		if ($this->isInterface() || $this->isTrait() || $this->isEnum()) {
			return null;
		}

		$nativeAttributes = $this->reflection->getAttributes(Attribute::class);
		if (count($nativeAttributes) === 1) {
			if (!$this->reflectionProvider->hasClass(Attribute::class)) {
				return null;
			}

			$attributeClass = $this->reflectionProvider->getClass(Attribute::class);
			$arguments = [];
			foreach ($nativeAttributes[0]->getArgumentsExpressions() as $i => $expression) {
				$arguments[] = new Arg($expression, false, false, [], is_int($i) ? null : new Identifier($i));
			}

			if (!$attributeClass->hasConstructor()) {
				return null;
			}
			$attributeConstructor = $attributeClass->getConstructor();
			$attributeConstructorVariant = $attributeConstructor->getOnlyVariant();

			if (count($arguments) === 0) {
				$flagType = $attributeConstructorVariant->getParameters()[0]->getDefaultValue();
			} else {
				$staticCall = ArgumentsNormalizer::reorderStaticCallArguments(
					$attributeConstructorVariant,
					new StaticCall(new FullyQualified(Attribute::class), $attributeConstructor->getName(), $arguments),
				);
				if ($staticCall === null) {
					return null;
				}
				$flagExpr = $staticCall->getArgs()[0]->value;
				$flagType = $this->initializerExprTypeResolver->getType($flagExpr, InitializerExprContext::fromClassReflection($this));
			}

			if (!$flagType instanceof ConstantIntegerType) {
				return null;
			}

			return $flagType->getValue();
		}

		return null;
	}

	public function getAttributeClassFlags(): int
	{
		$flags = $this->findAttributeFlags();
		if ($flags === null) {
			throw new ShouldNotHappenException();
		}

		return $flags;
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		if ($this->templateTypeMap !== null) {
			return $this->templateTypeMap;
		}

		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			$this->templateTypeMap = TemplateTypeMap::createEmpty();
			return $this->templateTypeMap;
		}

		$templateTypeScope = TemplateTypeScope::createWithClass($this->getName());

		$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $this->getTemplateTags()));

		$this->templateTypeMap = $templateTypeMap;

		return $templateTypeMap;
	}

	public function getActiveTemplateTypeMap(): TemplateTypeMap
	{
		if ($this->activeTemplateTypeMap !== null) {
			return $this->activeTemplateTypeMap;
		}
		$resolved = $this->resolvedTemplateTypeMap;
		if ($resolved !== null) {
			$templateTypeMap = $this->getTemplateTypeMap();
			return $this->activeTemplateTypeMap = $resolved->map(static function (string $name, Type $type) use ($templateTypeMap): Type {
				if ($type instanceof ErrorType) {
					$templateType = $templateTypeMap->getType($name);
					if ($templateType !== null) {
						return TemplateTypeHelper::resolveToBounds($templateType);
					}
				}

				return $type;
			});
		}

		return $this->activeTemplateTypeMap = $this->getTemplateTypeMap();
	}

	public function getPossiblyIncompleteActiveTemplateTypeMap(): TemplateTypeMap
	{
		return $this->resolvedTemplateTypeMap ?? $this->getTemplateTypeMap();
	}

	private function getDefaultCallSiteVarianceMap(): TemplateTypeVarianceMap
	{
		if ($this->defaultCallSiteVarianceMap !== null) {
			return $this->defaultCallSiteVarianceMap;
		}

		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			$this->defaultCallSiteVarianceMap = TemplateTypeVarianceMap::createEmpty();
			return $this->defaultCallSiteVarianceMap;
		}

		$map = [];
		foreach ($this->getTemplateTags() as $templateTag) {
			$map[$templateTag->getName()] = TemplateTypeVariance::createInvariant();
		}

		$this->defaultCallSiteVarianceMap = new TemplateTypeVarianceMap($map);
		return $this->defaultCallSiteVarianceMap;
	}

	public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
	{
		return $this->callSiteVarianceMap ??= $this->resolvedCallSiteVarianceMap ?? $this->getDefaultCallSiteVarianceMap();
	}

	public function isGeneric(): bool
	{
		if ($this->isGeneric === null) {
			if ($this->isEnum()) {
				return $this->isGeneric = false;
			}

			$this->isGeneric = count($this->getTemplateTags()) > 0;
		}

		return $this->isGeneric;
	}

	/**
	 * @param array<int, Type> $types
	 */
	public function typeMapFromList(array $types): TemplateTypeMap
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return TemplateTypeMap::createEmpty();
		}

		$map = [];
		$i = 0;
		foreach ($resolvedPhpDoc->getTemplateTags() as $tag) {
			$map[$tag->getName()] = $types[$i] ?? $tag->getBound();
			$i++;
		}

		return new TemplateTypeMap($map);
	}

	/**
	 * @param array<int, TemplateTypeVariance> $variances
	 */
	public function varianceMapFromList(array $variances): TemplateTypeVarianceMap
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return new TemplateTypeVarianceMap([]);
		}

		$map = [];
		$i = 0;
		foreach ($resolvedPhpDoc->getTemplateTags() as $tag) {
			$map[$tag->getName()] = $variances[$i] ?? TemplateTypeVariance::createInvariant();
			$i++;
		}

		return new TemplateTypeVarianceMap($map);
	}

	/** @return array<int, Type> */
	public function typeMapToList(TemplateTypeMap $typeMap): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		$list = [];
		foreach ($resolvedPhpDoc->getTemplateTags() as $tag) {
			$list[] = $typeMap->getType($tag->getName()) ?? $tag->getBound();
		}

		return $list;
	}

	/** @return array<int, TemplateTypeVariance> */
	public function varianceMapToList(TemplateTypeVarianceMap $varianceMap): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		$list = [];
		foreach ($resolvedPhpDoc->getTemplateTags() as $tag) {
			$list[] = $varianceMap->getVariance($tag->getName()) ?? TemplateTypeVariance::createInvariant();
		}

		return $list;
	}

	/**
	 * @param array<int, Type> $types
	 */
	public function withTypes(array $types): self
	{
		return new self(
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->fileTypeMapper,
			$this->stubPhpDocProvider,
			$this->phpDocInheritanceResolver,
			$this->phpVersion,
			$this->signatureMapProvider,
			$this->propertiesClassReflectionExtensions,
			$this->methodsClassReflectionExtensions,
			$this->allowedSubTypesClassReflectionExtensions,
			$this->requireExtendsPropertiesClassReflectionExtension,
			$this->requireExtendsMethodsClassReflectionExtension,
			$this->displayName,
			$this->reflection,
			$this->anonymousFilename,
			$this->typeMapFromList($types),
			$this->stubPhpDocBlock,
			$this->universalObjectCratesClasses,
			null,
			$this->resolvedCallSiteVarianceMap,
		);
	}

	/**
	 * @param array<int, TemplateTypeVariance> $variances
	 */
	public function withVariances(array $variances): self
	{
		return new self(
			$this->reflectionProvider,
			$this->initializerExprTypeResolver,
			$this->fileTypeMapper,
			$this->stubPhpDocProvider,
			$this->phpDocInheritanceResolver,
			$this->phpVersion,
			$this->signatureMapProvider,
			$this->propertiesClassReflectionExtensions,
			$this->methodsClassReflectionExtensions,
			$this->allowedSubTypesClassReflectionExtensions,
			$this->requireExtendsPropertiesClassReflectionExtension,
			$this->requireExtendsMethodsClassReflectionExtension,
			$this->displayName,
			$this->reflection,
			$this->anonymousFilename,
			$this->resolvedTemplateTypeMap,
			$this->stubPhpDocBlock,
			$this->universalObjectCratesClasses,
			null,
			$this->varianceMapFromList($variances),
		);
	}

	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock
	{
		if ($this->stubPhpDocBlock !== null) {
			return $this->stubPhpDocBlock;
		}

		$fileName = $this->getFileName();
		if (is_bool($this->reflectionDocComment)) {
			$docComment = $this->reflection->getDocComment();
			$this->reflectionDocComment = $docComment !== false ? $docComment : null;
		}

		if ($this->reflectionDocComment === null) {
			return null;
		}

		if ($this->resolvedPhpDocBlock !== false) {
			return $this->resolvedPhpDocBlock;
		}

		return $this->resolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc($fileName, $this->getName(), null, null, $this->reflectionDocComment);
	}

	public function getTraitContextResolvedPhpDoc(self $implementingClass): ?ResolvedPhpDocBlock
	{
		if (!$this->isTrait()) {
			throw new ShouldNotHappenException();
		}
		if ($implementingClass->isTrait()) {
			throw new ShouldNotHappenException();
		}
		$fileName = $this->getFileName();
		if (is_bool($this->reflectionDocComment)) {
			$docComment = $this->reflection->getDocComment();
			$this->reflectionDocComment = $docComment !== false ? $docComment : null;
		}

		if ($this->reflectionDocComment === null) {
			return null;
		}

		if ($this->traitContextResolvedPhpDocBlock !== false) {
			return $this->traitContextResolvedPhpDocBlock;
		}

		return $this->traitContextResolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc($fileName, $implementingClass->getName(), $this->getName(), null, $this->reflectionDocComment);
	}

	private function getFirstExtendsTag(): ?ExtendsTag
	{
		foreach ($this->getExtendsTags() as $tag) {
			return $tag;
		}

		return null;
	}

	/** @return array<string, ExtendsTag> */
	public function getExtendsTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getExtendsTags();
	}

	/** @return array<string, ImplementsTag> */
	public function getImplementsTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getImplementsTags();
	}

	/** @return array<string,TemplateTag> */
	public function getTemplateTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getTemplateTags();
	}

	/**
	 * @return array<string,ClassReflection>
	 */
	public function getAncestors(): array
	{
		$ancestors = $this->ancestors;

		if ($ancestors === null) {
			$ancestors = [
				$this->getName() => $this,
			];

			$addToAncestors = static function (string $name, ClassReflection $classReflection) use (&$ancestors): void {
				if (array_key_exists($name, $ancestors)) {
					return;
				}

				$ancestors[$name] = $classReflection;
			};

			foreach ($this->getInterfaces() as $interface) {
				$addToAncestors($interface->getName(), $interface);
				foreach ($interface->getAncestors() as $name => $ancestor) {
					$addToAncestors($name, $ancestor);
				}
			}

			foreach ($this->getTraits() as $trait) {
				$addToAncestors($trait->getName(), $trait);
				foreach ($trait->getAncestors() as $name => $ancestor) {
					$addToAncestors($name, $ancestor);
				}
			}

			$parent = $this->getParentClass();
			if ($parent !== null) {
				$addToAncestors($parent->getName(), $parent);
				foreach ($parent->getAncestors() as $name => $ancestor) {
					$addToAncestors($name, $ancestor);
				}
			}

			$this->ancestors = $ancestors;
		}

		return $ancestors;
	}

	public function getAncestorWithClassName(string $className): ?self
	{
		return $this->getAncestors()[$className] ?? null;
	}

	/**
	 * @param string[] $ancestorClasses
	 */
	private function isValidAncestorType(Type $type, array $ancestorClasses): bool
	{
		if (!$type instanceof GenericObjectType) {
			return false;
		}

		$reflection = $type->getClassReflection();
		if ($reflection === null) {
			return false;
		}

		return in_array($reflection->getName(), $ancestorClasses, true);
	}

	/**
	 * @return array<MixinTag>
	 */
	public function getMixinTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getMixinTags();
	}

	/**
	 * @return array<RequireExtendsTag>
	 */
	public function getRequireExtendsTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getRequireExtendsTags();
	}

	/**
	 * @return array<RequireImplementsTag>
	 */
	public function getRequireImplementsTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getRequireImplementsTags();
	}

	/**
	 * @return array<string, PropertyTag>
	 */
	public function getPropertyTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getPropertyTags();
	}

	/**
	 * @return array<string, MethodTag>
	 */
	public function getMethodTags(): array
	{
		$resolvedPhpDoc = $this->getResolvedPhpDoc();
		if ($resolvedPhpDoc === null) {
			return [];
		}

		return $resolvedPhpDoc->getMethodTags();
	}

	/**
	 * @return array<Type>
	 */
	public function getResolvedMixinTypes(): array
	{
		$types = [];
		foreach ($this->getMixinTags() as $mixinTag) {
			if (!$this->isGeneric()) {
				$types[] = $mixinTag->getType();
				continue;
			}

			$types[] = TemplateTypeHelper::resolveTemplateTypes(
				$mixinTag->getType(),
				$this->getActiveTemplateTypeMap(),
				$this->getCallSiteVarianceMap(),
				TemplateTypeVariance::createStatic(),
			);
		}

		return $types;
	}

	/**
	 * @return array<Type>|null
	 */
	public function getAllowedSubTypes(): ?array
	{
		foreach ($this->allowedSubTypesClassReflectionExtensions as $allowedSubTypesClassReflectionExtension) {
			if ($allowedSubTypesClassReflectionExtension->supports($this)) {
				return $allowedSubTypesClassReflectionExtension->getAllowedSubTypes($this);
			}
		}

		return null;
	}

}
