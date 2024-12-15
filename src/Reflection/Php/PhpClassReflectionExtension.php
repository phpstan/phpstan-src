<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Native\ExtendedNativeParameterReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\FunctionSignature;
use PHPStan\Reflection\SignatureMap\ParameterSignature;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
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
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
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
	implements PropertiesClassReflectionExtension, MethodsClassReflectionExtension
{

	/** @var ExtendedPropertyReflection[][] */
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
		private AnnotationsMethodsClassReflectionExtension $annotationsMethodsClassReflectionExtension,
		private AnnotationsPropertiesClassReflectionExtension $annotationsPropertiesClassReflectionExtension,
		private SignatureMapProvider $signatureMapProvider,
		private Parser $parser,
		private StubPhpDocProvider $stubPhpDocProvider,
		private ReflectionProvider\ReflectionProviderProvider $reflectionProviderProvider,
		private FileTypeMapper $fileTypeMapper,
		private bool $inferPrivatePropertyTypeFromConstructor,
	)
	{
	}

	public function evictPrivateSymbols(string $classCacheKey): void
	{
		foreach ($this->propertiesIncludingAnnotations as $key => $properties) {
			if ($key !== $classCacheKey) {
				continue;
			}
			foreach ($properties as $name => $property) {
				if (!$property->isPrivate()) {
					continue;
				}
				unset($this->propertiesIncludingAnnotations[$key][$name]);
			}
		}
		foreach ($this->nativeProperties as $key => $properties) {
			if ($key !== $classCacheKey) {
				continue;
			}
			foreach ($properties as $name => $property) {
				if (!$property->isPrivate()) {
					continue;
				}
				unset($this->nativeProperties[$key][$name]);
			}
		}
		foreach ($this->methodsIncludingAnnotations as $key => $methods) {
			if ($key !== $classCacheKey) {
				continue;
			}
			foreach ($methods as $name => $method) {
				if (!$method->isPrivate()) {
					continue;
				}
				unset($this->methodsIncludingAnnotations[$key][$name]);
			}
		}
		foreach ($this->nativeMethods as $key => $methods) {
			if ($key !== $classCacheKey) {
				continue;
			}
			foreach ($methods as $name => $method) {
				if (!$method->isPrivate()) {
					continue;
				}
				unset($this->nativeMethods[$key][$name]);
			}
		}
	}

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $classReflection->getNativeReflection()->hasProperty($propertyName);
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		if (!isset($this->propertiesIncludingAnnotations[$classReflection->getCacheKey()][$propertyName])) {
			$this->propertiesIncludingAnnotations[$classReflection->getCacheKey()][$propertyName] = $this->createProperty($classReflection, $propertyName, true);
		}

		return $this->propertiesIncludingAnnotations[$classReflection->getCacheKey()][$propertyName];
	}

	public function getNativeProperty(ClassReflection $classReflection, string $propertyName): PhpPropertyReflection
	{
		if (!isset($this->nativeProperties[$classReflection->getCacheKey()][$propertyName])) {
			/** @var PhpPropertyReflection $property */
			$property = $this->createProperty($classReflection, $propertyName, false);
			$this->nativeProperties[$classReflection->getCacheKey()][$propertyName] = $property;
		}

		return $this->nativeProperties[$classReflection->getCacheKey()][$propertyName];
	}

	private function createProperty(
		ClassReflection $classReflection,
		string $propertyName,
		bool $includingAnnotations,
	): ExtendedPropertyReflection
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

		if ($declaringClassReflection->isEnum()) {
			if (
				$propertyName === 'name'
				|| ($declaringClassReflection->isBackedEnum() && $propertyName === 'value')
			) {
				$types = [];
				foreach (array_keys($classReflection->getEnumCases()) as $name) {
					if ($propertyName === 'name') {
						$types[] = new ConstantStringType($name);
						continue;
					}

					$case = $classReflection->getEnumCase($name);
					$value = $case->getBackingValueType();
					if ($value === null) {
						throw new ShouldNotHappenException();
					}

					$types[] = $value;
				}

				return new PhpPropertyReflection($declaringClassReflection, null, null, TypeCombinator::union(...$types), $classReflection->getNativeReflection()->getProperty($propertyName), null, null, null, false, false, false, false);
			}
		}

		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;
		$isReadOnlyByPhpDoc = $classReflection->isImmutable();
		$isAllowedPrivateMutation = false;

		if (
			$includingAnnotations
			&& !$declaringClassReflection->isEnum()
			&& $this->annotationsPropertiesClassReflectionExtension->hasProperty($classReflection, $propertyName)
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

			if ($hierarchyDistances[$annotationProperty->getDeclaringClass()->getName()] <= $hierarchyDistances[$distanceDeclaringClass]) {
				return $annotationProperty;
			}
		}

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
			$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForProperty(
				$docComment,
				$declaringClassReflection,
				$declaringClassReflection->getFileName(),
				$declaringTraitName,
				$propertyName,
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
		$phpDocBlockClassReflection = $declaringClassReflection;

		if ($resolvedPhpDoc !== null) {
			$varTags = $resolvedPhpDoc->getVarTags();
			if (isset($varTags[0]) && count($varTags) === 1) {
				$phpDocType = $varTags[0]->getType();
			} elseif (isset($varTags[$propertyName])) {
				$phpDocType = $varTags[$propertyName]->getType();
			}

			$phpDocType = $phpDocType !== null ? TemplateTypeHelper::resolveTemplateTypes(
				$phpDocType,
				$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
				$phpDocBlockClassReflection->getCallSiteVarianceMap(),
				TemplateTypeVariance::createInvariant(),
			) : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$isReadOnlyByPhpDoc = $isReadOnlyByPhpDoc || $resolvedPhpDoc->isReadOnly();
			$isAllowedPrivateMutation = $resolvedPhpDoc->isAllowedPrivateMutation();
		}

		if ($phpDocType === null) {
			if (isset($constructorName)) {
				$constructorDocComment = $declaringClassReflection->getConstructor()->getDocComment();
				$nativeClassReflection = $declaringClassReflection->getNativeReflection();
				$positionalParameterNames = [];
				if ($nativeClassReflection->getConstructor() !== null) {
					$positionalParameterNames = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $nativeClassReflection->getConstructor()->getParameters());
				}
				$resolvedConstructorPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
					$constructorDocComment,
					$declaringClassReflection->getFileName(),
					$declaringClassReflection,
					$declaringTraitName,
					$constructorName,
					$positionalParameterNames,
				);
				$paramTags = $resolvedConstructorPhpDoc->getParamTags();
				if (isset($paramTags[$propertyReflection->getName()])) {
					$phpDocType = $paramTags[$propertyReflection->getName()]->getType();
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

		$nativeType = null;
		if ($propertyReflection->getType() !== null) {
			$nativeType = $propertyReflection->getType();
		}

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

		return new PhpPropertyReflection(
			$declaringClassReflection,
			$declaringTrait,
			$nativeType,
			$phpDocType,
			$propertyReflection,
			$getHook,
			$setHook,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isReadOnlyByPhpDoc,
			$isAllowedPrivateMutation,
		);
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getNativeReflection()->hasMethod($methodName);
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): ExtendedMethodReflection
	{
		if (isset($this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName])) {
			return $this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName];
		}

		$nativeMethodReflection = $classReflection->getNativeReflection()->getMethod($methodName);
		if (!isset($this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$nativeMethodReflection->getName()])) {
			$method = $this->createMethod($classReflection, $nativeMethodReflection, true);
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
		if (isset($this->nativeMethods[$classReflection->getCacheKey()][$methodName])) {
			return $this->nativeMethods[$classReflection->getCacheKey()][$methodName];
		}

		if (!$classReflection->getNativeReflection()->hasMethod($methodName)) {
			throw new ShouldNotHappenException();
		}

		$nativeMethodReflection = $classReflection->getNativeReflection()->getMethod($methodName);

		if (!isset($this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()])) {
			$method = $this->createMethod($classReflection, $nativeMethodReflection, false);
			$this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()] = $method;
		}

		return $this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()];
	}

	private function createMethod(
		ClassReflection $classReflection,
		ReflectionMethod $methodReflection,
		bool $includingAnnotations,
	): ExtendedMethodReflection
	{
		if ($includingAnnotations && $this->annotationsMethodsClassReflectionExtension->hasMethod($classReflection, $methodReflection->getName())) {
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

		if ($this->signatureMapProvider->hasMethodSignature($declaringClassName, $methodReflection->getName())) {
			$variantsByType = ['positional' => []];
			$reflectionMethod = null;
			$throwType = null;
			$asserts = Assertions::createEmpty();
			$acceptsNamedArguments = true;
			$selfOutType = null;
			$phpDocComment = null;
			if ($classReflection->getNativeReflection()->hasMethod($methodReflection->getName())) {
				$reflectionMethod = $classReflection->getNativeReflection()->getMethod($methodReflection->getName());
			}
			$methodSignaturesResult = $this->signatureMapProvider->getMethodSignatures($declaringClassName, $methodReflection->getName(), $reflectionMethod);
			foreach ($methodSignaturesResult as $signatureType => $methodSignatures) {
				if ($methodSignatures === null) {
					continue;
				}

				foreach ($methodSignatures as $methodSignature) {
					$phpDocParameterNameMapping = [];
					foreach ($methodSignature->getParameters() as $parameter) {
						$phpDocParameterNameMapping[$parameter->getName()] = $parameter->getName();
					}
					$stubPhpDocReturnType = null;
					$stubPhpDocParameterTypes = [];
					$stubPhpDocParameterVariadicity = [];
					$phpDocParameterTypes = [];
					$phpDocReturnType = null;
					$stubPhpDocPair = null;
					$stubPhpParameterOutTypes = [];
					$phpDocParameterOutTypes = [];
					$immediatelyInvokedCallableParameters = [];
					$closureThisParameters = [];
					$stubImmediatelyInvokedCallableParameters = [];
					$stubClosureThisParameters = [];
					if (count($methodSignatures) === 1) {
						$stubPhpDocPair = $this->findMethodPhpDocIncludingAncestors($declaringClass, $declaringClass, $methodReflection->getName(), array_map(static fn (ParameterSignature $parameterSignature): string => $parameterSignature->getName(), $methodSignature->getParameters()));
						if ($stubPhpDocPair !== null) {
							[$stubPhpDoc, $stubDeclaringClass] = $stubPhpDocPair;
							$templateTypeMap = $stubDeclaringClass->getActiveTemplateTypeMap();
							$callSiteVarianceMap = $stubDeclaringClass->getCallSiteVarianceMap();
							$returnTag = $stubPhpDoc->getReturnTag();
							$stubImmediatelyInvokedCallableParameters = array_map(static fn (bool $immediate) => TrinaryLogic::createFromBoolean($immediate), $stubPhpDoc->getParamsImmediatelyInvokedCallable());
							if ($returnTag !== null) {
								$stubPhpDocReturnType = TemplateTypeHelper::resolveTemplateTypes(
									$returnTag->getType(),
									$templateTypeMap,
									$callSiteVarianceMap,
									TemplateTypeVariance::createCovariant(),
								);
							}

							$stubClosureThisParameters = array_map(static fn ($tag) => $tag->getType(), $stubPhpDoc->getParamClosureThisTags());
							foreach ($stubPhpDoc->getParamTags() as $name => $paramTag) {
								$stubPhpDocParameterTypes[$name] = TemplateTypeHelper::resolveTemplateTypes(
									$paramTag->getType(),
									$templateTypeMap,
									$callSiteVarianceMap,
									TemplateTypeVariance::createContravariant(),
								);
								$stubPhpDocParameterVariadicity[$name] = $paramTag->isVariadic();
							}

							$throwsTag = $stubPhpDoc->getThrowsTag();
							if ($throwsTag !== null) {
								$throwType = $throwsTag->getType();
							}

							$asserts = Assertions::createFromResolvedPhpDocBlock($stubPhpDoc);
							$acceptsNamedArguments = $stubPhpDoc->acceptsNamedArguments();

							$selfOutTypeTag = $stubPhpDoc->getSelfOutTag();
							if ($selfOutTypeTag !== null) {
								$selfOutType = $selfOutTypeTag->getType();
							}

							foreach ($stubPhpDoc->getParamOutTags() as $name => $paramOutTag) {
								$stubPhpParameterOutTypes[$name] = TemplateTypeHelper::resolveTemplateTypes(
									$paramOutTag->getType(),
									$templateTypeMap,
									$callSiteVarianceMap,
									TemplateTypeVariance::createCovariant(),
								);
							}

							if ($declaringClassName === $stubDeclaringClass->getName() && $stubPhpDoc->hasPhpDocString()) {
								$phpDocComment = $stubPhpDoc->getPhpDocString();
							}
						}
					}
					if ($stubPhpDocPair === null && $reflectionMethod !== null && $reflectionMethod->getDocComment() !== false) {
						$filename = $reflectionMethod->getFileName();
						if ($filename !== false) {
							$phpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc(
								$filename,
								$declaringClassName,
								null,
								$reflectionMethod->getName(),
								$reflectionMethod->getDocComment(),
							);
							$throwsTag = $phpDocBlock->getThrowsTag();
							if ($throwsTag !== null) {
								$throwType = $throwsTag->getType();
							}
							$returnTag = $phpDocBlock->getReturnTag();
							if ($returnTag !== null && count($methodSignatures) === 1) {
								$phpDocReturnType = $returnTag->getType();
							}
							$immediatelyInvokedCallableParameters = array_map(static fn ($immediate) => TrinaryLogic::createFromBoolean($immediate), $phpDocBlock->getParamsImmediatelyInvokedCallable());
							$closureThisParameters = array_map(static fn ($tag) => $tag->getType(), $phpDocBlock->getParamClosureThisTags());
							foreach ($phpDocBlock->getParamTags() as $name => $paramTag) {
								$phpDocParameterTypes[$name] = $paramTag->getType();
							}
							$asserts = Assertions::createFromResolvedPhpDocBlock($phpDocBlock);
							$acceptsNamedArguments = $phpDocBlock->acceptsNamedArguments();

							$selfOutTypeTag = $phpDocBlock->getSelfOutTag();
							if ($selfOutTypeTag !== null) {
								$selfOutType = $selfOutTypeTag->getType();
							}

							if ($phpDocBlock->hasPhpDocString()) {
								$phpDocComment = $phpDocBlock->getPhpDocString();
							}

							foreach ($phpDocBlock->getParamOutTags() as $name => $paramOutTag) {
								$phpDocParameterOutTypes[$name] = $paramOutTag->getType();
							}

							$signatureParameters = $methodSignature->getParameters();
							foreach ($reflectionMethod->getParameters() as $paramI => $reflectionParameter) {
								if (!array_key_exists($paramI, $signatureParameters)) {
									continue;
								}

								$phpDocParameterNameMapping[$signatureParameters[$paramI]->getName()] = $reflectionParameter->getName();
							}
						}
					}
					$variantsByType[$signatureType][] = $this->createNativeMethodVariant($methodSignature, $stubPhpDocParameterTypes, $stubPhpDocParameterVariadicity, $stubPhpDocReturnType, $phpDocParameterTypes, $phpDocReturnType, $phpDocParameterNameMapping, $stubPhpParameterOutTypes, $phpDocParameterOutTypes, $stubImmediatelyInvokedCallableParameters, $immediatelyInvokedCallableParameters, $stubClosureThisParameters, $closureThisParameters, $signatureType !== 'named');
				}
			}

			if ($this->signatureMapProvider->hasMethodMetadata($declaringClassName, $methodReflection->getName())) {
				$hasSideEffects = TrinaryLogic::createFromBoolean($this->signatureMapProvider->getMethodMetadata($declaringClassName, $methodReflection->getName())['hasSideEffects']);
			} else {
				$hasSideEffects = TrinaryLogic::createMaybe();
			}
			return new NativeMethodReflection(
				$this->reflectionProviderProvider->getReflectionProvider(),
				$declaringClass,
				$methodReflection,
				$variantsByType['positional'],
				$variantsByType['named'] ?? null,
				$hasSideEffects,
				$throwType,
				$asserts,
				$acceptsNamedArguments,
				$selfOutType,
				$phpDocComment,
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
		$resolvedPhpDoc = null;
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
			[$resolvedPhpDoc, $phpDocBlockClassReflection] = $stubPhpDocPair;
		}

		if ($resolvedPhpDoc === null) {
			$docComment = $methodReflection->getDocComment() !== false ? $methodReflection->getDocComment() : null;
			$positionalParameterNames = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters());

			$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
				$docComment,
				$fileDeclaringClass->getFileName(),
				$fileDeclaringClass,
				$declaringTraitName,
				$methodReflection->getName(),
				$positionalParameterNames,
			);
			$phpDocBlockClassReflection = $fileDeclaringClass;
		}

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

		$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
		$immediatelyInvokedCallableParameters = array_map(static fn (bool $immediate) => TrinaryLogic::createFromBoolean($immediate), $resolvedPhpDoc->getParamsImmediatelyInvokedCallable());
		$closureThisParameters = array_map(static fn ($tag) => $tag->getType(), $resolvedPhpDoc->getParamClosureThisTags());

		foreach ($resolvedPhpDoc->getParamTags() as $paramName => $paramTag) {
			if (array_key_exists($paramName, $phpDocParameterTypes)) {
				continue;
			}
			$phpDocParameterTypes[$paramName] = $paramTag->getType();
		}
		foreach ($phpDocParameterTypes as $paramName => $paramType) {
			$phpDocParameterTypes[$paramName] = TemplateTypeHelper::resolveTemplateTypes(
				$paramType,
				$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
				$phpDocBlockClassReflection->getCallSiteVarianceMap(),
				TemplateTypeVariance::createContravariant(),
			);
		}

		$phpDocParameterOutTypes = [];
		foreach ($resolvedPhpDoc->getParamOutTags() as $paramName => $paramOutTag) {
			$phpDocParameterOutTypes[$paramName] = TemplateTypeHelper::resolveTemplateTypes(
				$paramOutTag->getType(),
				$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
				$phpDocBlockClassReflection->getCallSiteVarianceMap(),
				TemplateTypeVariance::createCovariant(),
			);
		}

		$nativeReturnType = TypehintHelper::decideTypeFromReflection(
			$methodReflection->getReturnType(),
			null,
			$actualDeclaringClass,
		);
		$phpDocReturnType = $this->getPhpDocReturnType($phpDocBlockClassReflection, $resolvedPhpDoc, $nativeReturnType);
		$phpDocThrowType = $resolvedPhpDoc->getThrowsTag() !== null ? $resolvedPhpDoc->getThrowsTag()->getType() : null;
		$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
		$isDeprecated = $resolvedPhpDoc->isDeprecated();
		$isInternal = $resolvedPhpDoc->isInternal();
		$isFinal = $resolvedPhpDoc->isFinal();
		$isPure = $resolvedPhpDoc->isPure();
		$asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
		$acceptsNamedArguments = $resolvedPhpDoc->acceptsNamedArguments();
		$selfOutType = $resolvedPhpDoc->getSelfOutTag() !== null ? $resolvedPhpDoc->getSelfOutTag()->getType() : null;
		$phpDocComment = null;
		if ($resolvedPhpDoc->hasPhpDocString()) {
			$phpDocComment = $resolvedPhpDoc->getPhpDocString();
		}

		return $this->methodReflectionFactory->create(
			$actualDeclaringClass,
			$declaringTrait,
			$methodReflection,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnType,
			$phpDocThrowType,
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
		);
	}

	/**
	 * @param array<string, Type> $stubPhpDocParameterTypes
	 * @param array<string, bool> $stubPhpDocParameterVariadicity
	 * @param array<string, Type> $phpDocParameterTypes
	 * @param array<string, string> $phpDocParameterNameMapping
	 * @param array<string, Type> $stubPhpDocParameterOutTypes
	 * @param array<string, Type> $phpDocParameterOutTypes
	 * @param array<string, TrinaryLogic> $stubImmediatelyInvokedCallableParameters
	 * @param array<string, TrinaryLogic> $immediatelyInvokedCallableParameters
	 * @param array<string, Type> $stubClosureThisParameters
	 * @param array<string, Type> $closureThisParameters
	 */
	private function createNativeMethodVariant(
		FunctionSignature $methodSignature,
		array $stubPhpDocParameterTypes,
		array $stubPhpDocParameterVariadicity,
		?Type $stubPhpDocReturnType,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		array $phpDocParameterNameMapping,
		array $stubPhpDocParameterOutTypes,
		array $phpDocParameterOutTypes,
		array $stubImmediatelyInvokedCallableParameters,
		array $immediatelyInvokedCallableParameters,
		array $stubClosureThisParameters,
		array $closureThisParameters,
		bool $usePhpDocParameterNames,
	): ExtendedFunctionVariant
	{
		$parameters = [];
		foreach ($methodSignature->getParameters() as $parameterSignature) {
			$type = null;
			$phpDocType = null;
			$parameterOutType = null;

			$phpDocParameterName = $phpDocParameterNameMapping[$parameterSignature->getName()] ?? $parameterSignature->getName();

			if (isset($stubPhpDocParameterTypes[$parameterSignature->getName()])) {
				$type = $stubPhpDocParameterTypes[$parameterSignature->getName()];
				$phpDocType = $stubPhpDocParameterTypes[$parameterSignature->getName()];
			} elseif (isset($phpDocParameterTypes[$phpDocParameterName])) {
				$phpDocType = $phpDocParameterTypes[$phpDocParameterName];
			}

			if (isset($stubPhpDocParameterOutTypes[$parameterSignature->getName()])) {
				$parameterOutType = $stubPhpDocParameterOutTypes[$parameterSignature->getName()];
			} elseif (isset($phpDocParameterOutTypes[$phpDocParameterName])) {
				$parameterOutType = $phpDocParameterOutTypes[$phpDocParameterName];
			}

			if (isset($stubImmediatelyInvokedCallableParameters[$parameterSignature->getName()])) {
				$immediatelyInvoked = $stubImmediatelyInvokedCallableParameters[$parameterSignature->getName()];
			} elseif (isset($immediatelyInvokedCallableParameters[$phpDocParameterName])) {
				$immediatelyInvoked = $immediatelyInvokedCallableParameters[$phpDocParameterName];
			} else {
				$immediatelyInvoked = TrinaryLogic::createMaybe();
			}

			$closureThisType = null;
			if (isset($stubClosureThisParameters[$parameterSignature->getName()])) {
				$closureThisType = $stubClosureThisParameters[$parameterSignature->getName()];
			} elseif (isset($closureThisParameters[$phpDocParameterName])) {
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
				$stubPhpDocParameterVariadicity[$parameterSignature->getName()] ?? $parameterSignature->isVariadic(),
				$parameterSignature->getDefaultValue(),
				$parameterOutType ?? $parameterSignature->getOutType(),
				$immediatelyInvoked,
				$closureThisType,
			);
		}

		if ($stubPhpDocReturnType !== null) {
			$returnType = $stubPhpDocReturnType;
			$phpDocReturnType = $stubPhpDocReturnType;
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
		[$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes] = $this->nodeScopeResolver->getPhpDocs($classScope, $methodNode);
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

		if ($returnTag->isExplicit() || $nativeReturnType->isSuperTypeOf($phpDocReturnType)->yes()) {
			return $phpDocReturnType;
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
		if (!$this->stubPhpDocProvider->isKnownClass($declaringClassName)) {
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

			return [$resolved, $ancestor];
		}

		return null;
	}

}
