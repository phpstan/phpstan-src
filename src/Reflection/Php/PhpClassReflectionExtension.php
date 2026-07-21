<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\ExtendedNativeParameterReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\ParameterAllowedConstantsMapProvider;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\FunctionSignature;
use PHPStan\Reflection\SignatureMap\ParameterSignature;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_slice;
use function count;
use function explode;
use function implode;
use function is_array;
use function sprintf;
use function strtolower;

final class PhpClassReflectionExtension
{

	/** @var array<string, true> shared LRU over the member cache keys below; first entry = least recently used */
	private array $memberCacheOrder = [];

	/** @var PhpPropertyReflection[][] */
	private array $propertiesIncludingAnnotations = [];

	/** @var PhpPropertyReflection[][] */
	private array $nativeProperties = [];

	/** @var ExtendedMethodReflection[][] */
	private array $methodsIncludingAnnotations = [];

	/** @var ExtendedMethodReflection[][] */
	private array $nativeMethods = [];

	/** @var array<string, array<string, Type>> */
	private array $propertyTypesCache = [];

	/** @var array<string, true> */
	private array $inferClassConstructorPropertyTypesInProcess = [];

	public function __construct(
		private ScopeFactory $scopeFactory,
		private NodeScopeResolver $nodeScopeResolver,
		private PhpMethodReflectionFactory $methodReflectionFactory,
		private PhpDocInheritanceResolver $phpDocInheritanceResolver,
		private DeprecationProvider $deprecationProvider,
		private AnnotationsMethodsClassReflectionExtension $annotationsMethodsClassReflectionExtension,
		private AnnotationsPropertiesClassReflectionExtension $annotationsPropertiesClassReflectionExtension,
		private SignatureMapProvider $signatureMapProvider,
		private Parser $parser,
		private StubPhpDocProvider $stubPhpDocProvider,
		private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		private FileTypeMapper $fileTypeMapper,
		private AttributeReflectionFactory $attributeReflectionFactory,
		private ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider,
		private bool $inferPrivatePropertyTypeFromConstructor,
		private PhpVersion $phpVersion,
		private int $memberCacheKeysMax,
	)
	{
	}

	/**
	 * Moves the cache key to the most-recently-used position of the shared LRU governing
	 * all four member caches; evicts the least recently used key's entries from all of
	 * them once the limit is reached. A limit of 0 means unlimited.
	 *
	 * Replaces the former evictPrivateSymbols(): instead of dropping only private members
	 * of the just-analysed class (public/protected members accumulated for the whole
	 * process, measured at hundreds of MB on large codebases), classes not used recently
	 * are evicted wholesale — misses are pure recomputation.
	 */
	private function touchMemberCacheKey(string $cacheKey): void
	{
		if (isset($this->memberCacheOrder[$cacheKey])) {
			unset($this->memberCacheOrder[$cacheKey]);
			$this->memberCacheOrder[$cacheKey] = true;
			return;
		}

		$this->memberCacheOrder[$cacheKey] = true;
		if ($this->memberCacheKeysMax === 0 || count($this->memberCacheOrder) <= $this->memberCacheKeysMax) {
			return;
		}

		$evictKey = array_key_first($this->memberCacheOrder);
		unset(
			$this->memberCacheOrder[$evictKey],
			$this->methodsIncludingAnnotations[$evictKey],
			$this->nativeMethods[$evictKey],
			$this->propertiesIncludingAnnotations[$evictKey],
			$this->nativeProperties[$evictKey],
		);
	}

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $classReflection->getNativeReflection()->hasProperty($propertyName);
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName, ClassMemberAccessAnswerer $scope): PhpPropertyReflection
	{
		$cacheKey = $classReflection->getCacheKey();
		if ($scope->isInClass()) {
			$cacheKey = sprintf('%s-%s', $cacheKey, $scope->getClassReflection()->getCacheKey());
		}
		$this->touchMemberCacheKey($cacheKey);
		if (!isset($this->propertiesIncludingAnnotations[$cacheKey][$propertyName])) {
			$this->propertiesIncludingAnnotations[$cacheKey][$propertyName] = $this->createProperty($classReflection, $propertyName, $scope, true);
		}

		return $this->propertiesIncludingAnnotations[$cacheKey][$propertyName];
	}

	public function getNativeProperty(ClassReflection $classReflection, string $propertyName): PhpPropertyReflection
	{
		$this->touchMemberCacheKey($classReflection->getCacheKey());
		if (!isset($this->nativeProperties[$classReflection->getCacheKey()][$propertyName])) {
			$property = $this->createProperty($classReflection, $propertyName, new OutOfClassScope(), false);
			$this->nativeProperties[$classReflection->getCacheKey()][$propertyName] = $property;
		}

		return $this->nativeProperties[$classReflection->getCacheKey()][$propertyName];
	}

	private function createProperty(
		ClassReflection $classReflection,
		string $propertyName,
		ClassMemberAccessAnswerer $scope,
		bool $includingAnnotations,
	): PhpPropertyReflection
	{
		$propertyReflection = $classReflection->getNativeReflection()->getProperty($propertyName);
		$propertyName = $propertyReflection->getName();
		$declaringClassName = $propertyReflection->getDeclaringClass()->getName();
		$declaringClassReflection = $classReflection->getAncestorWithClassName($declaringClassName);
		if ($declaringClassReflection === null) {
			throw new ShouldNotHappenException(sprintf(
				'Internal error: Expected to find an ancestor with class name %s on %s, but none was found.',
				$declaringClassName,
				$classReflection->getName(),
			));
		}

		$isUnitEnumInterfaceNameProperty = $this->phpVersion->supportsEnums()
			&& $propertyName === 'name'
			&& $declaringClassName === 'UnitEnum';

		if ($declaringClassReflection->isEnum() || $isUnitEnumInterfaceNameProperty) {
			if (
				$propertyName === 'name'
				|| ($declaringClassReflection->isBackedEnum() && $propertyName === 'value')
			) {
				if ($declaringClassReflection->isEnum()) {
					$types = [];
					foreach ($classReflection->getEnumCases() as $name => $case) {
						if ($propertyName === 'name') {
							$types[] = new ConstantStringType($name);
							continue;
						}

						$value = $case->getBackingValueType();
						if ($value === null) {
							throw new ShouldNotHappenException();
						}

						$types[] = $value;
					}

					$phpDocType = TypeCombinator::union(...$types);
					if (count($types) > ObjectType::ENUM_CASES_LIMIT) {
						// Very large enums would otherwise carry a union of hundreds of constant
						// members through every type operation. @see ObjectType::ENUM_CASES_LIMIT
						$phpDocType = $phpDocType->generalize(GeneralizePrecision::lessSpecific());
					}
					$nativeType = new MixedType();
				} else {
					$phpDocType = TypeCombinator::intersect(
						new StringType(),
						new AccessoryNonFalsyStringType(),
						new AccessoryDecimalIntegerStringType(inverse: true),
					);
					$nativeType = new StringType();
				}

				return new PhpPropertyReflection(
					$declaringClassReflection,
					null,
					$nativeType,
					$phpDocType,
					$phpDocType,
					$classReflection->getNativeReflection()->getProperty($propertyName),
					getHook: null,
					setHook: null,
					resolvedPhpDocBlock: null,
					deprecatedDescription: null,
					isDeprecated: false,
					isInternal: false,
					isReadOnlyByPhpDoc: false,
					isAllowedPrivateMutation: false,
					attributes: [],
					isFinal: false,
					readable: true,
					writable: false,
					private: false,
					public: true,
				);
			}
		}

		$deprecation = $this->deprecationProvider->getPropertyDeprecation($propertyReflection);
		$deprecatedDescription = $deprecation === null ? null : $deprecation->getDescription();
		$isDeprecated = $deprecation !== null;
		$isInternal = false;
		$isReadOnlyByPhpDoc = $classReflection->isImmutable();
		$isFinal = $classReflection->isFinal() || $propertyReflection->isFinal();
		$isAllowedPrivateMutation = false;

		$docComment = $propertyReflection->getDocComment() !== false
			? $propertyReflection->getDocComment()
			: null;

		$phpDocType = null;
		$resolvedPhpDoc = null;
		$declaringTraitName = $this->findPropertyTrait($propertyReflection);
		$constructorName = null;
		if ($propertyReflection->isPromoted()) {
			if ($declaringClassReflection->hasConstructor()) {
				$constructorName = $declaringClassReflection->getConstructor()->getName();
			}
		}

		if ($constructorName === null) {
			$currentResolvedPhpDoc = $this->stubPhpDocProvider->findPropertyPhpDoc($declaringClassName, $propertyName);
			if (
				$currentResolvedPhpDoc === null
				&& $declaringTraitName !== null
			) {
				$currentResolvedPhpDoc = $this->stubPhpDocProvider->findPropertyPhpDoc($declaringTraitName, $propertyName);
			}
			if ($currentResolvedPhpDoc === null && $docComment !== null) {
				$currentResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$declaringClassReflection->getFileName(),
					$declaringClassName,
					$declaringTraitName,
					null,
					$docComment,
				);
			}
			$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForProperty(
				$declaringClassReflection,
				$propertyName,
				$currentResolvedPhpDoc,
			);
		} elseif ($docComment !== null) {
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$declaringClassReflection->getFileName(),
				$declaringClassName,
				$declaringTraitName,
				$constructorName,
				$docComment,
			);
		}

		if ($resolvedPhpDoc !== null) {
			$varTags = $resolvedPhpDoc->getVarTags();
			if (isset($varTags[0]) && count($varTags) === 1) {
				$phpDocType = $varTags[0]->getType();
			} elseif (isset($varTags[$propertyName])) {
				$phpDocType = $varTags[$propertyName]->getType();
			}

			$phpDocType = $phpDocType !== null ? TemplateTypeHelper::resolveTemplateTypes(
				$phpDocType,
				$declaringClassReflection->getActiveTemplateTypeMap(),
				$declaringClassReflection->getCallSiteVarianceMap(),
				TemplateTypeVariance::createInvariant(),
			) : null;

			if (!$isDeprecated) {
				$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
				$isDeprecated = $resolvedPhpDoc->isDeprecated();
			}
			$isInternal = $resolvedPhpDoc->isInternal();
			$isReadOnlyByPhpDoc = $isReadOnlyByPhpDoc || $resolvedPhpDoc->isReadOnly();
			$isFinal = $isFinal || $resolvedPhpDoc->isFinal();
			$isAllowedPrivateMutation = $resolvedPhpDoc->isAllowedPrivateMutation();
		}

		if ($phpDocType === null) {
			if (isset($constructorName)) {
				$resolvedConstructorPhpDoc = $declaringClassReflection->getConstructor()->getResolvedPhpDoc();
				if ($resolvedConstructorPhpDoc !== null) {
					$paramTags = $resolvedConstructorPhpDoc->getParamTags();
					if (isset($paramTags[$propertyReflection->getName()])) {
						$phpDocType = $paramTags[$propertyReflection->getName()]->getType();
					}
				}
			}
		}

		if (
			$phpDocType === null
			&& $this->inferPrivatePropertyTypeFromConstructor
			&& $declaringClassReflection->getFileName() !== null
			&& $propertyReflection->isPrivate()
			&& !$propertyReflection->isPromoted()
			&& !$propertyReflection->hasType()
			&& $declaringClassReflection->hasConstructor()
			&& $declaringClassReflection->getConstructor()->getDeclaringClass()->getName() === $declaringClassReflection->getName()
		) {
			$phpDocType = $this->inferPrivatePropertyType(
				$propertyReflection->getName(),
				$declaringClassReflection->getConstructor(),
			);
		}

		$nativeType = TypehintHelper::decideTypeFromReflection($propertyReflection->getType(), selfClass: $declaringClassReflection);

		$declaringTrait = null;
		$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
		if (
			$declaringTraitName !== null && $reflectionProvider->hasClass($declaringTraitName)
		) {
			$declaringTrait = $reflectionProvider->getClass($declaringTraitName);
		}

		$getHook = null;
		$setHook = null;

		$betterReflection = $propertyReflection->getBetterReflection();
		if ($betterReflection->hasHook('get')) {
			$betterReflectionGetHook = $betterReflection->getHook('get');
			if ($betterReflectionGetHook === null) {
				throw new ShouldNotHappenException();
			}
			$getHook = $this->createUserlandMethodReflection(
				$declaringClassReflection,
				$declaringClassReflection,
				new ReflectionMethod($betterReflectionGetHook),
				$declaringTraitName,
			);

			if ($phpDocType !== null) {
				$getHookMethodReflectionVariant = $getHook->getOnlyVariant();
				$getHookMethodReflectionVariantPhpDocReturnType = $getHookMethodReflectionVariant->getPhpDocReturnType();
				if (
					$getHookMethodReflectionVariantPhpDocReturnType instanceof MixedType
					&& !$getHookMethodReflectionVariantPhpDocReturnType instanceof TemplateMixedType
					&& !$getHookMethodReflectionVariantPhpDocReturnType->isExplicitMixed()
				) {
					$getHook = $getHook->changePropertyGetHookPhpDocType($phpDocType);
				}
			}
		}

		if ($betterReflection->hasHook('set')) {
			$betterReflectionSetHook = $betterReflection->getHook('set');
			if ($betterReflectionSetHook === null) {
				throw new ShouldNotHappenException();
			}
			$setHook = $this->createUserlandMethodReflection(
				$declaringClassReflection,
				$declaringClassReflection,
				new ReflectionMethod($betterReflectionSetHook),
				$declaringTraitName,
			);

			if ($phpDocType !== null) {
				$setHookMethodReflectionVariant = $setHook->getOnlyVariant();
				$setHookMethodReflectionParameters = $setHookMethodReflectionVariant->getParameters();
				if (isset($setHookMethodReflectionParameters[0])) {
					$setHookMethodReflectionParameter = $setHookMethodReflectionParameters[0];
					$setHookMethodReflectionParameterPhpDocType = $setHookMethodReflectionParameter->getPhpDocType();
					if (
						$setHookMethodReflectionParameterPhpDocType instanceof MixedType
						&& !$setHookMethodReflectionParameterPhpDocType instanceof TemplateMixedType
						&& !$setHookMethodReflectionParameterPhpDocType->isExplicitMixed()
					) {
						$setHook = $setHook->changePropertySetHookPhpDocType($setHookMethodReflectionParameter->getName(), $phpDocType);
					}
				}
			}
		}

		$nativeProperty = new PhpPropertyReflection(
			$declaringClassReflection,
			$declaringTrait,
			$nativeType,
			$phpDocType,
			$phpDocType,
			$propertyReflection,
			$getHook,
			$setHook,
			$resolvedPhpDoc,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isReadOnlyByPhpDoc,
			$isAllowedPrivateMutation,
			$this->attributeReflectionFactory->fromNativeReflection($propertyReflection->getAttributes(), InitializerExprContext::fromClass($declaringClassReflection->getName(), $declaringClassReflection->getFileName())),
			$isFinal,
			true,
			true,
			$propertyReflection->isPrivate(),
			$propertyReflection->isPublic(),
		);

		if (
			$includingAnnotations
			&& !$declaringClassReflection->isEnum()
			&& !$propertyReflection->isStatic()
			&& ($classReflection->allowsDynamicProperties() || $scope->canReadProperty($nativeProperty))
			&& $this->annotationsPropertiesClassReflectionExtension->hasProperty($classReflection, $propertyName)
			&& (
				$nativeProperty->isPublic()
				|| !$scope->isInClass()
				|| $scope->getClassReflection()->getName() !== $declaringClassReflection->getName()
			)
		) {
			$hierarchyDistances = $classReflection->getClassHierarchyDistances();
			$annotationProperty = $this->annotationsPropertiesClassReflectionExtension->getProperty($classReflection, $propertyName);
			if (!isset($hierarchyDistances[$annotationProperty->getDeclaringClass()->getName()])) {
				throw new ShouldNotHappenException();
			}

			$distanceDeclaringClass = $propertyReflection->getDeclaringClass()->getName();
			$propertyTrait = $this->findPropertyTrait($propertyReflection);
			if ($propertyTrait !== null) {
				$distanceDeclaringClass = $propertyTrait;
			}
			if (!isset($hierarchyDistances[$distanceDeclaringClass])) {
				throw new ShouldNotHappenException();
			}

			if (
				$hierarchyDistances[$annotationProperty->getDeclaringClass()->getName()] <= $hierarchyDistances[$distanceDeclaringClass]
			) {
				if ($nativeType->isSuperTypeOf($annotationProperty->getReadableType())->yes() || !$scope->canReadProperty($nativeProperty)) {
					$nativeType = new MixedType();
				}

				return new PhpPropertyReflection(
					$annotationProperty->getDeclaringClass(),
					$declaringTrait,
					$nativeType,
					$annotationProperty->getReadableType(),
					$annotationProperty->getWritableType(),
					$propertyReflection,
					$getHook,
					$setHook,
					$nativeProperty->getResolvedPhpDoc(),
					$deprecatedDescription,
					$isDeprecated,
					$isInternal,
					$isReadOnlyByPhpDoc,
					$isAllowedPrivateMutation,
					$this->attributeReflectionFactory->fromNativeReflection($propertyReflection->getAttributes(), InitializerExprContext::fromClass($declaringClassReflection->getName(), $declaringClassReflection->getFileName())),
					$isFinal,
					$annotationProperty->isReadable(),
					$annotationProperty->isWritable(),
					false,
					true,
				);
			}
		}

		return $nativeProperty;
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getNativeReflection()->hasMethod($methodName);
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): ExtendedMethodReflection
	{
		$this->touchMemberCacheKey($classReflection->getCacheKey());
		if (isset($this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName])) {
			return $this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName];
		}

		$nativeMethodReflection = $classReflection->getNativeReflection()->getMethod($methodName);
		if (!isset($this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$nativeMethodReflection->getName()])) {
			$method = $this->createMethod($classReflection, $methodName, $nativeMethodReflection, true);
			$this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$nativeMethodReflection->getName()] = $method;
			if ($nativeMethodReflection->getName() !== $methodName) {
				$this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName] = $method;
			}
		}

		return $this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$nativeMethodReflection->getName()];
	}

	public function hasNativeMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $this->hasMethod($classReflection, $methodName);
	}

	public function getNativeMethod(ClassReflection $classReflection, string $methodName): ExtendedMethodReflection
	{
		$this->touchMemberCacheKey($classReflection->getCacheKey());
		if (isset($this->nativeMethods[$classReflection->getCacheKey()][$methodName])) {
			return $this->nativeMethods[$classReflection->getCacheKey()][$methodName];
		}

		if (!$classReflection->getNativeReflection()->hasMethod($methodName)) {
			throw new ShouldNotHappenException();
		}

		$nativeMethodReflection = $classReflection->getNativeReflection()->getMethod($methodName);

		if (!isset($this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()])) {
			$method = $this->createMethod($classReflection, $methodName, $nativeMethodReflection, false);
			$this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()] = $method;
		}

		return $this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()];
	}

	private function createMethod(
		ClassReflection $classReflection,
		string $methodName,
		ReflectionMethod $methodReflection,
		bool $includingAnnotations,
	): ExtendedMethodReflection
	{
		if ($includingAnnotations) {
			if ($this->annotationsMethodsClassReflectionExtension->hasMethod($classReflection, $methodReflection->getName())) {
				$hierarchyDistances = $classReflection->getClassHierarchyDistances();
				$annotationMethod = $this->annotationsMethodsClassReflectionExtension->getMethod($classReflection, $methodReflection->getName());
				if (!isset($hierarchyDistances[$annotationMethod->getDeclaringClass()->getName()])) {
					throw new ShouldNotHappenException();
				}

				$distanceDeclaringClass = $methodReflection->getDeclaringClass()->getName();
				$methodTrait = $this->findMethodTrait($methodReflection);
				if ($methodTrait !== null) {
					$distanceDeclaringClass = $methodTrait;
				}
				if (!isset($hierarchyDistances[$distanceDeclaringClass])) {
					throw new ShouldNotHappenException();
				}

				if ($hierarchyDistances[$annotationMethod->getDeclaringClass()->getName()] <= $hierarchyDistances[$distanceDeclaringClass]) {
					return $annotationMethod;
				}
			}

			return $this->getNativeMethod($classReflection, $methodName);
		}

		$declaringClassName = $methodReflection->getDeclaringClass()->getName();
		$declaringClass = $classReflection->getAncestorWithClassName($declaringClassName);

		if ($declaringClass === null) {
			throw new ShouldNotHappenException(sprintf(
				'Internal error: Expected to find an ancestor with class name %s on %s, but none was found.',
				$declaringClassName,
				$classReflection->getName(),
			));
		}

		if (
			$declaringClass->isEnum()
			&& $declaringClass->getName() !== 'UnitEnum'
			&& strtolower($methodReflection->getName()) === 'cases'
		) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach (array_keys($classReflection->getEnumCases()) as $name) {
				$arrayBuilder->setOffsetValueType(null, new EnumCaseObjectType($classReflection->getName(), $name));
			}

			return new EnumCasesMethodReflection($declaringClass, $arrayBuilder->getArray());
		}

		if (($declaringClass->isBuiltin() || $declaringClass->isEnum()) && $this->signatureMapProvider->hasMethodSignature($declaringClassName, $methodReflection->getName())) {
			$variantsByType = ['positional' => []];
			$throwType = null;
			$asserts = Assertions::createEmpty();
			$acceptsNamedArguments = true;
			$selfOutType = null;
			$phpDocComment = null;

			$isPure = null;
			if ($this->signatureMapProvider->hasMethodMetadata($declaringClassName, $methodReflection->getName())) {
				$methodMetadata = $this->signatureMapProvider->getMethodMetadata($declaringClassName, $methodReflection->getName());
				$hasSideEffects = $methodMetadata['hasSideEffects'] ?? true;
				$isPure = !$hasSideEffects;
			}

			$methodSignaturesResult = $this->signatureMapProvider->getMethodSignatures($declaringClassName, $methodReflection->getName(), $methodReflection);
			foreach ($methodSignaturesResult as $signatureType => $methodSignatures) {
				if ($methodSignatures === null) {
					continue;
				}

				foreach ($methodSignatures as $methodSignature) {
					$phpDocParameterNameMapping = [];
					foreach ($methodSignature->getParameters() as $parameter) {
						$phpDocParameterNameMapping[$parameter->getName()] = $parameter->getName();
					}
					$phpDocParameterTypes = [];
					$phpDocReturnType = null;
					$phpDocParameterOutTypes = [];
					$immediatelyInvokedCallableParameters = [];
					$closureThisParameters = [];
					$currentResolvedPhpDoc = null;
					$phpDocDeclaringClass = $declaringClass;
					$phpDocFromStubs = false;
					if (count($methodSignatures) === 1) {
						$stubPhpDocPair = $this->findMethodPhpDocIncludingAncestors($declaringClass, $declaringClass, $methodReflection->getName(), array_map(static fn (ParameterSignature $parameterSignature): string => $parameterSignature->getName(), $methodSignature->getParameters()));
						if ($stubPhpDocPair !== null) {
							[$currentResolvedPhpDoc, $phpDocDeclaringClass] = $stubPhpDocPair;
							$phpDocFromStubs = true;
						}
					}
					if (
						$currentResolvedPhpDoc === null
						&& $methodReflection->getDocComment() !== false
					) {
						$currentResolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
							$declaringClass,
							$methodReflection->getName(),
							$this->fileTypeMapper->getResolvedPhpDoc(
								$methodReflection->getFileName() === false ? null : $methodReflection->getFileName(),
								$declaringClassName,
								null,
								$methodReflection->getName(),
								$methodReflection->getDocComment(),
							),
							array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters()),
						);
					}

					if ($currentResolvedPhpDoc !== null) {
						$templateTypeMap = $phpDocDeclaringClass->getActiveTemplateTypeMap();
						$callSiteVarianceMap = $phpDocDeclaringClass->getCallSiteVarianceMap();
						$returnTag = $currentResolvedPhpDoc->getReturnTag();
						$immediatelyInvokedCallableParameters = array_map(static fn (bool $immediate) => TrinaryLogic::createFromBoolean($immediate), $currentResolvedPhpDoc->getParamsImmediatelyInvokedCallable());
						if ($returnTag !== null && count($methodSignatures) === 1) {
							$phpDocReturnType = TemplateTypeHelper::resolveTemplateTypes(
								$returnTag->getType(),
								$templateTypeMap,
								$callSiteVarianceMap,
								TemplateTypeVariance::createCovariant(),
							);
						}

						$closureThisParameters = array_map(static fn ($tag) => $tag->getType(), $currentResolvedPhpDoc->getParamClosureThisTags());
						foreach ($currentResolvedPhpDoc->getParamTags() as $name => $paramTag) {
							$phpDocParameterTypes[$name] = TemplateTypeHelper::resolveTemplateTypes(
								$paramTag->getType(),
								$templateTypeMap,
								$callSiteVarianceMap,
								TemplateTypeVariance::createContravariant(),
							);
						}

						$throwsTag = $currentResolvedPhpDoc->getThrowsTag();
						if ($throwsTag !== null) {
							$throwType = $throwsTag->getType();
						}

						$asserts = Assertions::createFromResolvedPhpDocBlock($currentResolvedPhpDoc);
						$acceptsNamedArguments = $currentResolvedPhpDoc->acceptsNamedArguments();
						$isPure ??= $currentResolvedPhpDoc->isPure();

						$selfOutTypeTag = $currentResolvedPhpDoc->getSelfOutTag();
						if ($selfOutTypeTag !== null) {
							$selfOutType = $selfOutTypeTag->getType();
						}

						foreach ($currentResolvedPhpDoc->getParamOutTags() as $name => $paramOutTag) {
							$phpDocParameterOutTypes[$name] = TemplateTypeHelper::resolveTemplateTypes(
								$paramOutTag->getType(),
								$templateTypeMap,
								$callSiteVarianceMap,
								TemplateTypeVariance::createCovariant(),
							);
						}

						if ($currentResolvedPhpDoc->hasPhpDocString()) {
							$phpDocComment = $currentResolvedPhpDoc->getPhpDocString();
						}

						if (!$phpDocFromStubs) {
							$signatureParameters = $methodSignature->getParameters();
							foreach ($methodReflection->getParameters() as $paramI => $reflectionParameter) {
								if (!array_key_exists($paramI, $signatureParameters)) {
									continue;
								}

								$phpDocParameterNameMapping[$signatureParameters[$paramI]->getName()] = $reflectionParameter->getName();
							}
						}
					}
					$variantsByType[$signatureType][] = $this->createNativeMethodVariant($declaringClassName, $methodReflection->getName(), $methodSignature, $phpDocParameterTypes, $phpDocReturnType, $phpDocParameterNameMapping, $phpDocParameterOutTypes, $immediatelyInvokedCallableParameters, $closureThisParameters, $phpDocFromStubs, $signatureType !== 'named');
				}
			}

			if ($isPure === null) {
				$classResolvedPhpDoc = $declaringClass->getResolvedPhpDoc();
				if ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsPure()) {
					$isPure = true;
				} elseif ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsImpure()) {
					$isPure = false;
				}
			}

			return new NativeMethodReflection(
				$this->reflectionProviderProvider->getReflectionProvider(),
				$declaringClass,
				$methodReflection,
				$currentResolvedPhpDoc ?? null,
				$variantsByType['positional'],
				$variantsByType['named'] ?? null,
				$isPure !== null ? TrinaryLogic::createFromBoolean(!$isPure) : TrinaryLogic::createMaybe(),
				$throwType,
				$asserts,
				$acceptsNamedArguments,
				$selfOutType,
				$phpDocComment,
				$this->attributeReflectionFactory->fromNativeReflection($methodReflection->getAttributes(), InitializerExprContext::fromClassMethod($declaringClassName, null, $methodReflection->getName(), null)),
			);
		}

		return $this->createUserlandMethodReflection(
			$declaringClass,
			$declaringClass,
			$methodReflection,
			$this->findMethodTrait($methodReflection),
		);
	}

	public function createUserlandMethodReflection(ClassReflection $fileDeclaringClass, ClassReflection $actualDeclaringClass, ReflectionMethod $methodReflection, ?string $declaringTraitName): PhpMethodReflection
	{
		$deprecation = $this->deprecationProvider->getMethodDeprecation($methodReflection);
		$deprecatedDescription = $deprecation === null ? null : $deprecation->getDescription();
		$isDeprecated = $deprecation !== null;
		$currentResolvedPhpDoc = null;
		$stubPhpDocPair = $this->findMethodPhpDocIncludingAncestors($fileDeclaringClass, $fileDeclaringClass, $methodReflection->getName(), array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters()));
		$phpDocBlockClassReflection = $fileDeclaringClass;

		$methodDeclaringClass = $methodReflection->getBetterReflection()->getDeclaringClass();

		if ($stubPhpDocPair === null && $methodDeclaringClass->isTrait()) {
			if (! $methodReflection->getDeclaringClass()->isTrait() || $methodDeclaringClass->getName() !== $methodReflection->getDeclaringClass()->getName()) {
				$stubPhpDocPair = $this->findMethodPhpDocIncludingAncestors(
					$this->reflectionProviderProvider->getReflectionProvider()->getClass($methodDeclaringClass->getName()),
					$this->reflectionProviderProvider->getReflectionProvider()->getClass($methodReflection->getDeclaringClass()->getName()),
					$methodReflection->getName(),
					array_map(
						static fn (ReflectionParameter $parameter): string => $parameter->getName(),
						$methodReflection->getParameters(),
					),
				);
			}
		}

		if ($stubPhpDocPair !== null) {
			[$currentResolvedPhpDoc, $phpDocBlockClassReflection] = $stubPhpDocPair;
		}

		if ($currentResolvedPhpDoc === null && $methodReflection->getDocComment() !== false) {
			$currentResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$actualDeclaringClass->getFileName(),
				$actualDeclaringClass->getName(),
				$declaringTraitName,
				$methodReflection->getName(),
				$methodReflection->getDocComment(),
			);
		}

		$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
			$actualDeclaringClass,
			$methodReflection->getName(),
			$currentResolvedPhpDoc,
			array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters()),
		);

		$declaringTrait = null;
		$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
		if (
			$declaringTraitName !== null && $reflectionProvider->hasClass($declaringTraitName)
		) {
			$declaringTrait = $reflectionProvider->getClass($declaringTraitName);
		}

		$phpDocParameterTypes = [];
		if ($methodReflection->isConstructor()) {
			foreach ($methodReflection->getParameters() as $parameter) {
				if (!$parameter->isPromoted()) {
					continue;
				}

				if (!$methodReflection->getDeclaringClass()->hasProperty($parameter->getName())) {
					continue;
				}

				$parameterProperty = $methodReflection->getDeclaringClass()->getProperty($parameter->getName());
				if (!$parameterProperty->isPromoted()) {
					continue;
				}
				if ($parameterProperty->getDocComment() === false) {
					continue;
				}

				$propertyDocblock = $this->fileTypeMapper->getResolvedPhpDoc(
					$fileDeclaringClass->getFileName(),
					$fileDeclaringClass->getName(),
					$declaringTraitName,
					$methodReflection->getName(),
					$parameterProperty->getDocComment(),
				);
				$varTags = $propertyDocblock->getVarTags();
				if (isset($varTags[0]) && count($varTags) === 1) {
					$phpDocType = $varTags[0]->getType();
				} elseif (isset($varTags[$parameter->getName()])) {
					$phpDocType = $varTags[$parameter->getName()]->getType();
				} else {
					continue;
				}

				$phpDocParameterTypes[$parameter->getName()] = $phpDocType;
			}
		}

		$nativeReturnType = TypehintHelper::decideTypeFromReflection(
			$methodReflection->getReturnType(),
			selfClass: $actualDeclaringClass,
		);

		$isPure = null;
		$pureUnlessCallableIsImpureParameters = [];
		if ($actualDeclaringClass->isBuiltin() || $actualDeclaringClass->isEnum()) {
			foreach (array_keys($actualDeclaringClass->getAncestors()) as $className) {
				if ($this->signatureMapProvider->hasMethodMetadata($className, $methodReflection->getName())) {
					$methodMetadata = $this->signatureMapProvider->getMethodMetadata($className, $methodReflection->getName());
					$hasSideEffects = $methodMetadata['hasSideEffects'] ?? true;
					$isPure = !$hasSideEffects;
					$pureUnlessCallableIsImpureParameters += $methodMetadata['pureUnlessCallableIsImpureParameters'] ?? [];

					break;
				}
			}
		}

		$phpDocParameterOutTypes = [];
		$phpDocReturnType = null;
		$templateTypeMap = TemplateTypeMap::createEmpty();
		$immediatelyInvokedCallableParameters = [];
		$closureThisParameters = [];
		$phpDocThrowType = null;
		$isInternal = false;
		$isFinal = false;
		$asserts = Assertions::createEmpty();
		$acceptsNamedArguments = true;
		$selfOutType = null;
		$phpDocComment = null;
		if ($resolvedPhpDoc !== null) {
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
			$immediatelyInvokedCallableParameters = array_map(static fn (bool $immediate) => TrinaryLogic::createFromBoolean($immediate), $resolvedPhpDoc->getParamsImmediatelyInvokedCallable());
			$closureThisParameters = array_map(static fn ($tag) => $tag->getType(), $resolvedPhpDoc->getParamClosureThisTags());
			foreach ($resolvedPhpDoc->getParamsPureUnlessCallableIsImpure() as $paramName => $isPureUnlessCallableIsImpure) {
				$pureUnlessCallableIsImpureParameters[$paramName] = $isPureUnlessCallableIsImpure;
			}
			$phpDocReturnType = $this->getPhpDocReturnType($phpDocBlockClassReflection, $resolvedPhpDoc, $nativeReturnType);
			$phpDocThrowType = $resolvedPhpDoc->getThrowsTag() !== null ? $resolvedPhpDoc->getThrowsTag()->getType() : null;
			foreach ($resolvedPhpDoc->getParamTags() as $paramName => $paramTag) {
				if (array_key_exists($paramName, $phpDocParameterTypes)) {
					continue;
				}
				$phpDocParameterTypes[$paramName] = $paramTag->getType();
			}
			foreach ($resolvedPhpDoc->getParamOutTags() as $paramName => $paramOutTag) {
				$phpDocParameterOutTypes[$paramName] = TemplateTypeHelper::resolveTemplateTypes(
					$paramOutTag->getType(),
					$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
					$phpDocBlockClassReflection->getCallSiteVarianceMap(),
					TemplateTypeVariance::createCovariant(),
				);
			}
			if (!$isDeprecated) {
				$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
				$isDeprecated = $resolvedPhpDoc->isDeprecated();
			}
			$isInternal = $resolvedPhpDoc->isInternal();
			$isFinal = $resolvedPhpDoc->isFinal();
			$isPure ??= $resolvedPhpDoc->isPure();
			$asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
			$acceptsNamedArguments = $resolvedPhpDoc->acceptsNamedArguments();
			$selfOutType = $resolvedPhpDoc->getSelfOutTag() !== null ? $resolvedPhpDoc->getSelfOutTag()->getType() : null;
			if ($resolvedPhpDoc->hasPhpDocString()) {
				$phpDocComment = $resolvedPhpDoc->getPhpDocString();
			}
		}

		if ($isPure === null) {
			$classResolvedPhpDoc = $phpDocBlockClassReflection->getResolvedPhpDoc();
			if ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsPure()) {
				if (
					strtolower($methodReflection->getName()) === '__construct'
					|| (
						($phpDocReturnType === null || !$phpDocReturnType->isVoid()->yes())
						&& !$nativeReturnType->isVoid()->yes()
					)
				) {
					$isPure = true;
				}
			} elseif ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsImpure()) {
				$isPure = false;
			}
		}

		foreach ($phpDocParameterTypes as $paramName => $paramType) {
			$phpDocParameterTypes[$paramName] = TemplateTypeHelper::resolveTemplateTypes(
				$paramType,
				$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
				$phpDocBlockClassReflection->getCallSiteVarianceMap(),
				TemplateTypeVariance::createContravariant(),
			);
		}

		return $this->methodReflectionFactory->create(
			$actualDeclaringClass,
			$declaringTrait,
			$methodReflection,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnType,
			$phpDocThrowType,
			$resolvedPhpDoc,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isFinal,
			$isPure,
			$asserts,
			$selfOutType,
			$phpDocComment,
			$phpDocParameterOutTypes,
			$immediatelyInvokedCallableParameters,
			$closureThisParameters,
			$acceptsNamedArguments,
			$this->attributeReflectionFactory->fromNativeReflection($methodReflection->getAttributes(), InitializerExprContext::fromClassMethod($actualDeclaringClass->getName(), $declaringTraitName, $methodReflection->getName(), $actualDeclaringClass->getFileName())),
			$pureUnlessCallableIsImpureParameters,
		);
	}

	/**
	 * @param array<string, Type> $phpDocParameterTypes
	 * @param array<string, string> $phpDocParameterNameMapping
	 * @param array<string, Type> $phpDocParameterOutTypes
	 * @param array<string, TrinaryLogic> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $closureThisParameters
	 */
	private function createNativeMethodVariant(
		string $declaringClassName,
		string $methodName,
		FunctionSignature $methodSignature,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		array $phpDocParameterNameMapping,
		array $phpDocParameterOutTypes,
		array $immediatelyInvokedCallableParameters,
		array $closureThisParameters,
		bool $phpDocFromStubs,
		bool $usePhpDocParameterNames,
	): ExtendedFunctionVariant
	{
		$parameters = [];
		foreach ($methodSignature->getParameters() as $parameterSignature) {
			$type = null;
			$phpDocType = null;
			$parameterOutType = null;

			$phpDocParameterName = $phpDocParameterNameMapping[$parameterSignature->getName()] ?? $parameterSignature->getName();

			if (isset($phpDocParameterTypes[$phpDocParameterName])) {
				$phpDocType = $phpDocParameterTypes[$phpDocParameterName];
				$type = $phpDocFromStubs ? $phpDocType : TypehintHelper::decideType($parameterSignature->getType(), $phpDocType);
			}

			if (isset($phpDocParameterOutTypes[$phpDocParameterName])) {
				$parameterOutType = $phpDocParameterOutTypes[$phpDocParameterName];
			}

			if (isset($immediatelyInvokedCallableParameters[$phpDocParameterName])) {
				$immediatelyInvoked = $immediatelyInvokedCallableParameters[$phpDocParameterName];
			} else {
				$immediatelyInvoked = TrinaryLogic::createMaybe();
			}

			$closureThisType = null;
			if (isset($closureThisParameters[$phpDocParameterName])) {
				$closureThisType = $closureThisParameters[$phpDocParameterName];
			}

			$parameters[] = new ExtendedNativeParameterReflection(
				$usePhpDocParameterNames
					? $phpDocParameterName
					: $parameterSignature->getName(),
				$parameterSignature->isOptional(),
				$type ?? $parameterSignature->getType(),
				$phpDocType ?? new MixedType(),
				$parameterSignature->getNativeType(),
				$parameterSignature->passedByReference(),
				$parameterSignature->isVariadic(),
				$parameterSignature->getDefaultValue(),
				$parameterOutType ?? $parameterSignature->getOutType(),
				$immediatelyInvoked,
				$closureThisType,
				[],
				$this->allowedConstantsMapProvider->getForMethodParameter($declaringClassName, $methodName, $parameterSignature->getName()),
				// pure-unless-callable-is-impure is not threaded here because no built-in method
				// carries it (there are no Class::method entries in functionMetadata.php).
				TrinaryLogic::createNo(),
			);
		}

		if ($phpDocFromStubs && $phpDocReturnType !== null) {
			$returnType = $phpDocReturnType;
		} else {
			$returnType = TypehintHelper::decideType($methodSignature->getReturnType(), $phpDocReturnType);
		}

		return new ExtendedFunctionVariant(
			TemplateTypeMap::createEmpty(),
			null,
			$parameters,
			$methodSignature->isVariadic(),
			$returnType,
			$phpDocReturnType ?? new MixedType(),
			$methodSignature->getNativeReturnType(),
		);
	}

	private function findPropertyTrait(ReflectionProperty $propertyReflection): ?string
	{
		$declaringClass = $propertyReflection->getBetterReflection()->getDeclaringClass();
		if ($declaringClass->isTrait()) {
			if ($propertyReflection->getDeclaringClass()->isTrait() && $propertyReflection->getDeclaringClass()->getName() === $declaringClass->getName()) {
				return null;
			}

			return $declaringClass->getName();
		}

		return null;
	}

	private function findMethodTrait(
		ReflectionMethod $methodReflection,
	): ?string
	{
		$declaringClass = $methodReflection->getBetterReflection()->getDeclaringClass();
		if ($declaringClass->isTrait()) {
			if ($methodReflection->getDeclaringClass()->isTrait() && $declaringClass->getName() === $methodReflection->getDeclaringClass()->getName()) {
				return null;
			}

			return $declaringClass->getName();
		}

		return null;
	}

	private function inferPrivatePropertyType(
		string $propertyName,
		MethodReflection $constructor,
	): ?Type
	{
		$declaringClassName = $constructor->getDeclaringClass()->getName();
		if (isset($this->inferClassConstructorPropertyTypesInProcess[$declaringClassName])) {
			return null;
		}
		$this->inferClassConstructorPropertyTypesInProcess[$declaringClassName] = true;
		$propertyTypes = $this->inferAndCachePropertyTypes($constructor);
		unset($this->inferClassConstructorPropertyTypesInProcess[$declaringClassName]);
		if (array_key_exists($propertyName, $propertyTypes)) {
			return $propertyTypes[$propertyName];
		}

		return null;
	}

	/**
	 * @return array<string, Type>
	 */
	private function inferAndCachePropertyTypes(
		MethodReflection $constructor,
	): array
	{
		$declaringClass = $constructor->getDeclaringClass();
		if (isset($this->propertyTypesCache[$declaringClass->getName()])) {
			return $this->propertyTypesCache[$declaringClass->getName()];
		}
		if ($declaringClass->getFileName() === null) {
			return $this->propertyTypesCache[$declaringClass->getName()] = [];
		}

		$fileName = $declaringClass->getFileName();
		$nodes = $this->parser->parseFile($fileName);
		$classNode = $this->findClassNode($declaringClass->getName(), $nodes);
		if ($classNode === null) {
			return $this->propertyTypesCache[$declaringClass->getName()] = [];
		}

		$methodNode = $this->findConstructorNode($constructor->getName(), $classNode->stmts);
		if ($methodNode === null || $methodNode->stmts === null || count($methodNode->stmts) === 0) {
			return $this->propertyTypesCache[$declaringClass->getName()] = [];
		}

		$classNameParts = explode('\\', $declaringClass->getName());
		$namespace = null;
		if (count($classNameParts) > 1) {
			$namespace = implode('\\', array_slice($classNameParts, 0, -1));
		}

		$classScope = $this->scopeFactory->create(ScopeContext::create($fileName));
		if ($namespace !== null) {
			$classScope = $classScope->enterNamespace($namespace);
		}
		$classScope = $classScope->enterClass($declaringClass);
		[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes, , , , $phpDocPureUnlessCallableIsImpureParameters] = $this->nodeScopeResolver->getPhpDocs($classScope, $methodNode);
		$methodScope = $classScope->enterClassMethod(
			$methodNode,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnType,
			$phpDocThrowType,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isFinal,
			$isPure,
			$acceptsNamedArguments,
			$asserts,
			$selfOutType,
			$phpDocComment,
			$phpDocParameterOutTypes,
			$phpDocImmediatelyInvokedCallableParameters,
			$phpDocClosureThisTypeParameters,
			false,
			null,
			$phpDocPureUnlessCallableIsImpureParameters,
		);

		$propertyTypes = [];
		foreach ($methodNode->stmts as $statement) {
			if (!$statement instanceof Node\Stmt\Expression) {
				continue;
			}

			$expr = $statement->expr;
			if (!$expr instanceof Node\Expr\Assign) {
				continue;
			}

			if (!$expr->var instanceof Node\Expr\PropertyFetch) {
				continue;
			}

			$propertyFetch = $expr->var;
			if (
				!$propertyFetch->var instanceof Node\Expr\Variable
				|| $propertyFetch->var->name !== 'this'
				|| !$propertyFetch->name instanceof Node\Identifier
			) {
				continue;
			}

			$propertyType = $methodScope->getType($expr->expr);
			if ($propertyType instanceof ErrorType || $propertyType instanceof NeverType) {
				continue;
			}

			$propertyType = $propertyType->generalize(GeneralizePrecision::lessSpecific());
			if ($propertyType->isConstantArray()->yes()) {
				$propertyType = new ArrayType(new MixedType(true), new MixedType(true));
			}

			$propertyTypes[$propertyFetch->name->toString()] = $propertyType;
		}

		return $this->propertyTypesCache[$declaringClass->getName()] = $propertyTypes;
	}

	/**
	 * @param Node[] $nodes
	 */
	private function findClassNode(string $className, array $nodes): ?Class_
	{
		foreach ($nodes as $node) {
			if (
				$node instanceof Class_
				&& $node->namespacedName !== null
				&& $node->namespacedName->toString() === $className
			) {
				return $node;
			}
			if (
				!$node instanceof Namespace_
				&& !$node instanceof Declare_
			) {
				continue;
			}
			$subNodeNames = $node->getSubNodeNames();
			foreach ($subNodeNames as $subNodeName) {
				$subNode = $node->{$subNodeName};
				if (!is_array($subNode)) {
					$subNode = [$subNode];
				}
				$result = $this->findClassNode($className, $subNode);
				if ($result === null) {
					continue;
				}
				return $result;
			}
		}
		return null;
	}

	/**
	 * @param Node\Stmt[] $classStatements
	 */
	private function findConstructorNode(string $methodName, array $classStatements): ?ClassMethod
	{
		foreach ($classStatements as $statement) {
			if (
				$statement instanceof ClassMethod
				&& $statement->name->toString() === $methodName
			) {
				return $statement;
			}
		}
		return null;
	}

	private function getPhpDocReturnType(ClassReflection $phpDocBlockClassReflection, ResolvedPhpDocBlock $resolvedPhpDoc, Type $nativeReturnType): ?Type
	{
		$returnTag = $resolvedPhpDoc->getReturnTag();

		if ($returnTag === null) {
			return null;
		}

		$phpDocReturnType = $returnTag->getType();
		$phpDocReturnType = TemplateTypeHelper::resolveTemplateTypes(
			$phpDocReturnType,
			$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
			$phpDocBlockClassReflection->getCallSiteVarianceMap(),
			TemplateTypeVariance::createCovariant(),
		);

		if ($returnTag->isExplicit()) {
			return $phpDocReturnType;
		}

		if ($nativeReturnType->isSuperTypeOf($phpDocReturnType)->yes()) {
			return $phpDocReturnType;
		}

		if ($phpDocReturnType instanceof UnionType) {
			$types = [];
			foreach ($phpDocReturnType->getTypes() as $innerType) {
				if (!$nativeReturnType->isSuperTypeOf($innerType)->yes()) {
					continue;
				}

				$types[] = $innerType;
			}

			if (count($types) === 0) {
				return null;
			}

			return TypeCombinator::union(...$types);
		}

		return null;
	}

	/**
	 * @param array<int, string> $positionalParameterNames
	 * @return array{ResolvedPhpDocBlock, ClassReflection}|null
	 */
	private function findMethodPhpDocIncludingAncestors(
		ClassReflection $declaringClass,
		ClassReflection $implementingClass,
		string $methodName,
		array $positionalParameterNames,
	): ?array
	{
		$declaringClassName = $declaringClass->getName();
		$resolved = $this->stubPhpDocProvider->findMethodPhpDoc($declaringClassName, $implementingClass->getName(), $methodName, $positionalParameterNames);
		if ($resolved !== null) {
			return [$resolved, $declaringClass];
		}
		$isKnownClass = $this->stubPhpDocProvider->isKnownClass($declaringClassName);
		if (!$isKnownClass && !$declaringClass->isBuiltin()) {
			return null;
		}

		$ancestors = $declaringClass->getAncestors();
		foreach ($ancestors as $ancestor) {
			if ($ancestor->getName() === $declaringClassName) {
				continue;
			}
			if (!$ancestor->hasNativeMethod($methodName)) {
				continue;
			}

			$resolved = $this->stubPhpDocProvider->findMethodPhpDoc($ancestor->getName(), $ancestor->getName(), $methodName, $positionalParameterNames);
			if ($resolved === null) {
				continue;
			}

			if (!$isKnownClass && $ancestor->isGeneric()) {
				continue;
			}

			return [$resolved, $ancestor];
		}

		return null;
	}

}
