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
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\PhpDocBlock;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\ParameterSignature;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\TypeUtils;

class PhpClassReflectionExtension
	implements PropertiesClassReflectionExtension, MethodsClassReflectionExtension
{

	/** @var ScopeFactory */
	private $scopeFactory;

	/** @var NodeScopeResolver */
	private $nodeScopeResolver;

	/** @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory */
	private $methodReflectionFactory;

	/** @var \PHPStan\Type\FileTypeMapper */
	private $fileTypeMapper;

	/** @var \PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension */
	private $annotationsMethodsClassReflectionExtension;

	/** @var \PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension */
	private $annotationsPropertiesClassReflectionExtension;

	/** @var \PHPStan\Reflection\SignatureMap\SignatureMapProvider */
	private $signatureMapProvider;

	/** @var \PHPStan\Parser\Parser */
	private $parser;

	/** @var \PHPStan\PhpDoc\StubPhpDocProvider */
	private $stubPhpDocProvider;

	/** @var bool */
	private $inferPrivatePropertyTypeFromConstructor;

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $reflectionProvider;

	/** @var string[] */
	private $universalObjectCratesClasses;

	/** @var \PHPStan\Reflection\PropertyReflection[][] */
	private $propertiesIncludingAnnotations = [];

	/** @var \PHPStan\Reflection\Php\PhpPropertyReflection[][] */
	private $nativeProperties;

	/** @var \PHPStan\Reflection\MethodReflection[][] */
	private $methodsIncludingAnnotations = [];

	/** @var \PHPStan\Reflection\MethodReflection[][] */
	private $nativeMethods = [];

	/** @var array<string, array<string, Type>> */
	private $propertyTypesCache = [];

	/** @var array<string, true> */
	private $inferClassConstructorPropertyTypesInProcess = [];

	/**
	 * @param \PHPStan\Analyser\ScopeFactory $scopeFactory
	 * @param \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver
	 * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
	 * @param \PHPStan\Type\FileTypeMapper $fileTypeMapper
	 * @param \PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension $annotationsMethodsClassReflectionExtension
	 * @param \PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension $annotationsPropertiesClassReflectionExtension
	 * @param \PHPStan\Reflection\SignatureMap\SignatureMapProvider $signatureMapProvider
	 * @param \PHPStan\Parser\Parser $parser
	 * @param \PHPStan\PhpDoc\StubPhpDocProvider $stubPhpDocProvider
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param bool $inferPrivatePropertyTypeFromConstructor
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		ScopeFactory $scopeFactory,
		NodeScopeResolver $nodeScopeResolver,
		PhpMethodReflectionFactory $methodReflectionFactory,
		FileTypeMapper $fileTypeMapper,
		AnnotationsMethodsClassReflectionExtension $annotationsMethodsClassReflectionExtension,
		AnnotationsPropertiesClassReflectionExtension $annotationsPropertiesClassReflectionExtension,
		SignatureMapProvider $signatureMapProvider,
		Parser $parser,
		StubPhpDocProvider $stubPhpDocProvider,
		ReflectionProvider $reflectionProvider,
		bool $inferPrivatePropertyTypeFromConstructor,
		array $universalObjectCratesClasses
	)
	{
		$this->scopeFactory = $scopeFactory;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->methodReflectionFactory = $methodReflectionFactory;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->annotationsMethodsClassReflectionExtension = $annotationsMethodsClassReflectionExtension;
		$this->annotationsPropertiesClassReflectionExtension = $annotationsPropertiesClassReflectionExtension;
		$this->signatureMapProvider = $signatureMapProvider;
		$this->parser = $parser;
		$this->stubPhpDocProvider = $stubPhpDocProvider;
		$this->reflectionProvider = $reflectionProvider;
		$this->inferPrivatePropertyTypeFromConstructor = $inferPrivatePropertyTypeFromConstructor;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
	}

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $classReflection->getNativeReflection()->hasProperty($propertyName);
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		if (!isset($this->propertiesIncludingAnnotations[$classReflection->getCacheKey()][$propertyName])) {
			$this->propertiesIncludingAnnotations[$classReflection->getCacheKey()][$propertyName] = $this->createProperty($classReflection, $propertyName, true);
		}

		return $this->propertiesIncludingAnnotations[$classReflection->getCacheKey()][$propertyName];
	}

	public function getNativeProperty(ClassReflection $classReflection, string $propertyName): PhpPropertyReflection
	{
		if (!isset($this->nativeProperties[$classReflection->getCacheKey()][$propertyName])) {
			/** @var \PHPStan\Reflection\Php\PhpPropertyReflection $property */
			$property = $this->createProperty($classReflection, $propertyName, false);
			$this->nativeProperties[$classReflection->getCacheKey()][$propertyName] = $property;
		}

		return $this->nativeProperties[$classReflection->getCacheKey()][$propertyName];
	}

	private function createProperty(
		ClassReflection $classReflection,
		string $propertyName,
		bool $includingAnnotations
	): PropertyReflection
	{
		$propertyReflection = $classReflection->getNativeReflection()->getProperty($propertyName);
		$propertyName = $propertyReflection->getName();
		$declaringClassName = $propertyReflection->getDeclaringClass()->getName();
		$declaringClassReflection = $classReflection->getAncestorWithClassName($declaringClassName);
		if ($declaringClassReflection === null) {
			throw new \PHPStan\ShouldNotHappenException(sprintf(
				'Internal error: Expected to find an ancestor with class name %s on %s, but none was found.',
				$declaringClassName,
				$classReflection->getName()
			));
		}

		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;

		if ($includingAnnotations && $this->annotationsPropertiesClassReflectionExtension->hasProperty($classReflection, $propertyName)) {
			$hierarchyDistances = $classReflection->getClassHierarchyDistances();
			$annotationProperty = $this->annotationsPropertiesClassReflectionExtension->getProperty($classReflection, $propertyName);
			if (!isset($hierarchyDistances[$annotationProperty->getDeclaringClass()->getName()])) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if (!isset($hierarchyDistances[$propertyReflection->getDeclaringClass()->getName()])) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			if ($hierarchyDistances[$annotationProperty->getDeclaringClass()->getName()] < $hierarchyDistances[$propertyReflection->getDeclaringClass()->getName()]) {
				return $annotationProperty;
			}
		}

		$docComment = $propertyReflection->getDocComment() !== false
			? $propertyReflection->getDocComment()
			: null;

		$declaringTraitName = null;
		$phpDocType = null;
		$resolvedPhpDoc = $this->stubPhpDocProvider->findPropertyPhpDoc(
			$declaringClassName,
			$propertyReflection->getName()
		);
		$stubPhpDocString = null;
		if ($resolvedPhpDoc === null) {
			if ($declaringClassReflection->getFileName() !== false) {
				$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForProperty(
					$docComment,
					$declaringClassReflection,
					null,
					$propertyName,
					$declaringClassReflection->getFileName(),
					null,
					[],
					[]
				);
				if ($phpDocBlock !== null) {
					$declaringTraitName = $this->findPropertyTrait(
						$phpDocBlock,
						$propertyReflection
					);
					$phpDocBlockClassReflection = $phpDocBlock->getClassReflection();
					$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
						$phpDocBlock->getFile(),
						$phpDocBlockClassReflection->getName(),
						$declaringTraitName,
						null,
						$phpDocBlock->getDocComment()
					);
				}
			}
		} else {
			$phpDocBlockClassReflection = $declaringClassReflection;
			$stubPhpDocString = $resolvedPhpDoc->getPhpDocString();
		}

		if ($resolvedPhpDoc !== null) {
			$varTags = $resolvedPhpDoc->getVarTags();
			if (isset($varTags[0]) && count($varTags) === 1) {
				$phpDocType = $varTags[0]->getType();
			} elseif (isset($varTags[$propertyName])) {
				$phpDocType = $varTags[$propertyName]->getType();
			}
			if (!isset($phpDocBlockClassReflection)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$phpDocType = $phpDocType !== null ? TemplateTypeHelper::resolveTemplateTypes(
				$phpDocType,
				$phpDocBlockClassReflection->getActiveTemplateTypeMap()
			) : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
		}

		if (
			$phpDocType === null
			&& $this->inferPrivatePropertyTypeFromConstructor
			&& $declaringClassReflection->getFileName() !== false
			&& $propertyReflection->isPrivate()
			&& (!method_exists($propertyReflection, 'hasType') || !$propertyReflection->hasType())
			&& $declaringClassReflection->hasConstructor()
			&& $declaringClassReflection->getConstructor()->getDeclaringClass()->getName() === $declaringClassReflection->getName()
		) {
			$phpDocType = $this->inferPrivatePropertyType(
				$propertyReflection->getName(),
				$declaringClassReflection->getConstructor()
			);
		}

		$nativeType = null;
		if (method_exists($propertyReflection, 'getType') && $propertyReflection->getType() !== null) {
			$nativeType = $propertyReflection->getType();
		}

		$declaringTrait = null;
		if (
			$declaringTraitName !== null && $this->reflectionProvider->hasClass($declaringTraitName)
		) {
			$declaringTrait = $this->reflectionProvider->getClass($declaringTraitName);
		}

		return new PhpPropertyReflection(
			$declaringClassReflection,
			$declaringTrait,
			$nativeType,
			$phpDocType,
			$propertyReflection,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$stubPhpDocString
		);
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getNativeReflection()->hasMethod($methodName);
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		if (isset($this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName])) {
			return $this->methodsIncludingAnnotations[$classReflection->getCacheKey()][$methodName];
		}

		$nativeMethodReflection = new NativeBuiltinMethodReflection($classReflection->getNativeReflection()->getMethod($methodName));
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
		$hasMethod = $this->hasMethod($classReflection, $methodName);
		if ($hasMethod) {
			return true;
		}

		if ($methodName === '__get' && UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
			$this->reflectionProvider,
			$this->universalObjectCratesClasses,
			$classReflection
		)) {
			return true;
		}

		return false;
	}

	public function getNativeMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		if (isset($this->nativeMethods[$classReflection->getCacheKey()][$methodName])) {
			return $this->nativeMethods[$classReflection->getCacheKey()][$methodName];
		}

		if ($classReflection->getNativeReflection()->hasMethod($methodName)) {
			$nativeMethodReflection = new NativeBuiltinMethodReflection(
				$classReflection->getNativeReflection()->getMethod($methodName)
			);
		} else {
			if (
				$methodName !== '__get'
				|| !UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
					$this->reflectionProvider,
					$this->universalObjectCratesClasses,
					$classReflection
				)) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			$nativeMethodReflection = new FakeBuiltinMethodReflection(
				$methodName,
				$classReflection->getNativeReflection()
			);
		}

		if (!isset($this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()])) {
			$method = $this->createMethod($classReflection, $nativeMethodReflection, false);
			$this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()] = $method;
		}

		return $this->nativeMethods[$classReflection->getCacheKey()][$nativeMethodReflection->getName()];
	}

	private function createMethod(
		ClassReflection $classReflection,
		BuiltinMethodReflection $methodReflection,
		bool $includingAnnotations
	): MethodReflection
	{
		if ($includingAnnotations && $this->annotationsMethodsClassReflectionExtension->hasMethod($classReflection, $methodReflection->getName())) {
			$hierarchyDistances = $classReflection->getClassHierarchyDistances();
			$annotationMethod = $this->annotationsMethodsClassReflectionExtension->getMethod($classReflection, $methodReflection->getName());
			if (!isset($hierarchyDistances[$annotationMethod->getDeclaringClass()->getName()])) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if (!isset($hierarchyDistances[$methodReflection->getDeclaringClass()->getName()])) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			if ($hierarchyDistances[$annotationMethod->getDeclaringClass()->getName()] < $hierarchyDistances[$methodReflection->getDeclaringClass()->getName()]) {
				return $annotationMethod;
			}
		}
		$declaringClassName = $methodReflection->getDeclaringClass()->getName();
		$signatureMapMethodName = sprintf('%s::%s', $declaringClassName, $methodReflection->getName());
		$declaringClass = $classReflection->getAncestorWithClassName($declaringClassName);

		if ($declaringClass === null) {
			throw new \PHPStan\ShouldNotHappenException(sprintf(
				'Internal error: Expected to find an ancestor with class name %s on %s, but none was found.',
				$declaringClassName,
				$classReflection->getName()
			));
		}

		if ($this->signatureMapProvider->hasFunctionSignature($signatureMapMethodName)) {
			$variantName = $signatureMapMethodName;
			$variantNames = [];
			$i = 0;
			while ($this->signatureMapProvider->hasFunctionSignature($variantName)) {
				$variantNames[] = $variantName;
				$i++;
				$variantName = sprintf($signatureMapMethodName . '\'' . $i);
			}

			$stubPhpDocString = null;
			$variants = [];
			foreach ($variantNames as $innerVariantName) {
				$methodSignature = $this->signatureMapProvider->getFunctionSignature($innerVariantName, $declaringClassName);
				$phpDocReturnType = null;
				$stubPhpDocParameterTypes = [];
				$stubPhpDocParameterVariadicity = [];
				if (count($variantNames) === 1) {
					$stubPhpDoc = $this->findMethodPhpDocIncludingAncestors($declaringClassName, $methodReflection->getName());
					if ($stubPhpDoc !== null) {
						$stubPhpDocString = $stubPhpDoc->getPhpDocString();
						$templateTypeMap = $declaringClass->getActiveTemplateTypeMap();
						$returnTag = $stubPhpDoc->getReturnTag();
						if ($returnTag !== null) {
							$stubPhpDocReturnType = $returnTag->getType();
							$phpDocReturnType = TemplateTypeHelper::resolveTemplateTypes(
								$stubPhpDocReturnType,
								$templateTypeMap
							);
						}

						foreach ($stubPhpDoc->getParamTags() as $name => $paramTag) {
							$stubPhpDocParameterTypes[$name] = TemplateTypeHelper::resolveTemplateTypes(
								$paramTag->getType(),
								$templateTypeMap
							);
							$stubPhpDocParameterVariadicity[$name] = $paramTag->isVariadic();
						}
					}
				}
				$variants[] = new FunctionVariant(
					TemplateTypeMap::createEmpty(),
					null,
					array_map(static function (ParameterSignature $parameterSignature) use ($stubPhpDocParameterTypes, $stubPhpDocParameterVariadicity): NativeParameterReflection {
						return new NativeParameterReflection(
							$parameterSignature->getName(),
							$parameterSignature->isOptional(),
							$stubPhpDocParameterTypes[$parameterSignature->getName()] ?? $parameterSignature->getType(),
							$parameterSignature->passedByReference(),
							$stubPhpDocParameterVariadicity[$parameterSignature->getName()] ?? $parameterSignature->isVariadic(),
							null,
							isset($stubPhpDocParameterTypes[$parameterSignature->getName()])
						);
					}, $methodSignature->getParameters()),
					$methodSignature->isVariadic(),
					$phpDocReturnType ?? $methodSignature->getReturnType()
				);
			}

			if ($this->signatureMapProvider->hasFunctionMetadata($signatureMapMethodName)) {
				$hasSideEffects = TrinaryLogic::createFromBoolean($this->signatureMapProvider->getFunctionMetadata($signatureMapMethodName)['hasSideEffects']);
			} else {
				$hasSideEffects = TrinaryLogic::createMaybe();
			}
			return new NativeMethodReflection(
				$this->reflectionProvider,
				$declaringClass,
				$methodReflection,
				$variants,
				$hasSideEffects,
				$stubPhpDocString
			);
		}

		$declaringTraitName = $this->findMethodTrait($methodReflection);
		$resolvedPhpDoc = $this->findMethodPhpDocIncludingAncestors($declaringClassName, $methodReflection->getName());
		$stubPhpDocString = null;
		$phpDocBlock = null;
		if ($resolvedPhpDoc === null) {
			if ($declaringClass->getFileName() !== false) {
				$docComment = $methodReflection->getDocComment();
				$positionalParameterNames = array_map(static function (\ReflectionParameter $parameter): string {
					return $parameter->getName();
				}, $methodReflection->getParameters());
				$phpDocBlock = PhpDocBlock::resolvePhpDocBlockForMethod(
					$docComment,
					$declaringClass,
					$declaringTraitName,
					$methodReflection->getName(),
					$declaringClass->getFileName(),
					null,
					$positionalParameterNames,
					$positionalParameterNames
				);

				if ($phpDocBlock !== null) {
					$phpDocBlockClassReflection = $phpDocBlock->getClassReflection();
					$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
						$phpDocBlock->getFile(),
						$phpDocBlockClassReflection->getName(),
						$phpDocBlock->getTrait(),
						$methodReflection->getName(),
						$phpDocBlock->getDocComment()
					);
					$isPhpDocBlockExplicit = $phpDocBlock->isExplicit();
				}
			}
		} else {
			$phpDocBlockClassReflection = $declaringClass;
			$isPhpDocBlockExplicit = true;
			$stubPhpDocString = $resolvedPhpDoc->getPhpDocString();
		}

		$declaringTrait = null;
		if (
			$declaringTraitName !== null && $this->reflectionProvider->hasClass($declaringTraitName)
		) {
			$declaringTrait = $this->reflectionProvider->getClass($declaringTraitName);
		}

		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTypes = [];
		$phpDocReturnType = null;
		$phpDocThrowType = null;
		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		if ($resolvedPhpDoc !== null) {
			if (!isset($phpDocBlockClassReflection) || !isset($isPhpDocBlockExplicit)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
			$phpDocParameterTypes = array_map(static function (ParamTag $tag) use ($phpDocBlockClassReflection): Type {
				return TemplateTypeHelper::resolveTemplateTypes(
					$tag->getType(),
					$phpDocBlockClassReflection->getActiveTemplateTypeMap()
				);
			}, $resolvedPhpDoc->getParamTags());
			if ($phpDocBlock !== null) {
				$phpDocParameterTypes = $phpDocBlock->transformArrayKeysWithParameterNameMapping($phpDocParameterTypes);
			}
			$nativeReturnType = TypehintHelper::decideTypeFromReflection(
				$methodReflection->getReturnType(),
				null,
				$declaringClass->getName()
			);
			$phpDocReturnType = $this->getPhpDocReturnType($phpDocBlockClassReflection, $isPhpDocBlockExplicit, $resolvedPhpDoc, $nativeReturnType);
			$phpDocThrowType = $resolvedPhpDoc->getThrowsTag() !== null ? $resolvedPhpDoc->getThrowsTag()->getType() : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$isFinal = $resolvedPhpDoc->isFinal();
		}

		return $this->methodReflectionFactory->create(
			$declaringClass,
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
			$stubPhpDocString
		);
	}

	private function findPropertyTrait(
		PhpDocBlock $phpDocBlock,
		\ReflectionProperty $propertyReflection
	): ?string
	{
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$phpDocBlock->getFile(),
			$phpDocBlock->getClassReflection()->getName(),
			null,
			null,
			$phpDocBlock->getDocComment()
		);
		if (count($resolvedPhpDoc->getVarTags()) > 0) {
			return null;
		}

		$declaringClass = $propertyReflection->getDeclaringClass();
		$traits = $declaringClass->getTraits();
		while (count($traits) > 0) {
			/** @var \ReflectionClass<object> $traitReflection */
			$traitReflection = array_pop($traits);
			$traits = array_merge($traits, $traitReflection->getTraits());
			if (!$traitReflection->hasProperty($propertyReflection->getName())) {
				continue;
			}

			$traitResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$phpDocBlock->getFile(),
				$phpDocBlock->getClassReflection()->getName(),
				$traitReflection->getName(),
				null,
				$phpDocBlock->getDocComment()
			);
			if (
				count($traitResolvedPhpDoc->getVarTags()) > 0
			) {
				return $traitReflection->getName();
			}
		}

		return null;
	}

	private function findMethodTrait(
		BuiltinMethodReflection $methodReflection
	): ?string
	{
		$declaringClass = $methodReflection->getDeclaringClass();
		if (
			$methodReflection->getFileName() === $declaringClass->getFileName()
			&& $methodReflection->getStartLine() >= $declaringClass->getStartLine()
			&& $methodReflection->getEndLine() <= $declaringClass->getEndLine()
		) {
			return null;
		}

		$declaringClass = $methodReflection->getDeclaringClass();
		$traitAliases = $declaringClass->getTraitAliases();
		if (array_key_exists($methodReflection->getName(), $traitAliases)) {
			return explode('::', $traitAliases[$methodReflection->getName()])[0];
		}

		foreach ($this->collectTraits($declaringClass) as $traitReflection) {
			if (!$traitReflection->hasMethod($methodReflection->getName())) {
				continue;
			}

			if (
				$methodReflection->getFileName() === $traitReflection->getFileName()
				&& $methodReflection->getStartLine() >= $traitReflection->getStartLine()
				&& $methodReflection->getEndLine() <= $traitReflection->getEndLine()
			) {
				return $traitReflection->getName();
			}
		}

		return null;
	}

	/**
	 * @param \ReflectionClass $class
	 * @return \ReflectionClass[]
	 */
	private function collectTraits(\ReflectionClass $class): array
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

	private function inferPrivatePropertyType(
		string $propertyName,
		MethodReflection $constructor
	): Type
	{
		$declaringClassName = $constructor->getDeclaringClass()->getName();
		if (isset($this->inferClassConstructorPropertyTypesInProcess[$declaringClassName])) {
			return new MixedType();
		}
		$this->inferClassConstructorPropertyTypesInProcess[$declaringClassName] = true;
		$propertyTypes = $this->inferAndCachePropertyTypes($constructor);
		unset($this->inferClassConstructorPropertyTypesInProcess[$declaringClassName]);
		if (array_key_exists($propertyName, $propertyTypes)) {
			return $propertyTypes[$propertyName];
		}

		return new MixedType();
	}

	/**
	 * @param \PHPStan\Reflection\MethodReflection $constructor
	 * @return array<string, Type>
	 */
	private function inferAndCachePropertyTypes(
		MethodReflection $constructor
	): array
	{
		$declaringClass = $constructor->getDeclaringClass();
		if (isset($this->propertyTypesCache[$declaringClass->getName()])) {
			return $this->propertyTypesCache[$declaringClass->getName()];
		}
		if ($declaringClass->getFileName() === false) {
			return $this->propertyTypesCache[$declaringClass->getName()] = [];
		}

		$fileName = $declaringClass->getFileName();
		$nodes = $this->parser->parseFile($fileName);
		$classNode = $this->findClassNode($declaringClass->getName(), $nodes);
		if ($classNode === null) {
			return $this->propertyTypesCache[$declaringClass->getName()] = [];
		}

		$methodNode = $this->findConstructorNode($constructor->getName(), $classNode->stmts);
		if ($methodNode === null || $methodNode->stmts === null) {
			return $this->propertyTypesCache[$declaringClass->getName()] = [];
		}

		$classNameParts = explode('\\', $declaringClass->getName());
		$namespace = null;
		if (count($classNameParts) > 0) {
			$namespace = implode('\\', array_slice($classNameParts, 0, -1));
		}

		$classScope = $this->scopeFactory->create(
			ScopeContext::create($fileName),
			false,
			$constructor,
			$namespace
		)->enterClass($declaringClass);
		[$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal] = $this->nodeScopeResolver->getPhpDocs($classScope, $methodNode);
		$methodScope = $classScope->enterClassMethod(
			$methodNode,
			$templateTypeMap,
			$phpDocParameterTypes,
			$phpDocReturnType,
			$phpDocThrowType,
			$deprecatedDescription,
			$isDeprecated,
			$isInternal,
			$isFinal
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

			$propertyType = TypeUtils::generalizeType($propertyType);
			if ($propertyType instanceof ConstantArrayType) {
				$propertyType = new ArrayType(new MixedType(true), new MixedType(true));
			}

			$propertyTypes[$propertyFetch->name->toString()] = $propertyType;
		}

		return $this->propertyTypesCache[$declaringClass->getName()] = $propertyTypes;
	}

	/**
	 * @param string $className
	 * @param \PhpParser\Node[] $nodes
	 * @return \PhpParser\Node\Stmt\Class_|null
	 */
	private function findClassNode(string $className, array $nodes): ?Class_
	{
		foreach ($nodes as $node) {
			if (
				$node instanceof Class_
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
	 * @param string $methodName
	 * @param \PhpParser\Node\Stmt[] $classStatements
	 * @return \PhpParser\Node\Stmt\ClassMethod|null
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

	private function getPhpDocReturnType(ClassReflection $phpDocBlockClassReflection, bool $isPhpDocBlockExplicit, ResolvedPhpDocBlock $resolvedPhpDoc, Type $nativeReturnType): ?Type
	{
		$returnTag = $resolvedPhpDoc->getReturnTag();

		if ($returnTag === null) {
			return null;
		}

		$phpDocReturnType = $returnTag->getType();
		$phpDocReturnType = TemplateTypeHelper::resolveTemplateTypes(
			$phpDocReturnType,
			$phpDocBlockClassReflection->getActiveTemplateTypeMap()
		);

		if ($isPhpDocBlockExplicit || $nativeReturnType->isSuperTypeOf($phpDocReturnType)->yes()) {
			return $phpDocReturnType;
		}

		return null;
	}

	private function findMethodPhpDocIncludingAncestors(string $declaringClassName, string $methodName): ?ResolvedPhpDocBlock
	{
		$resolved = $this->stubPhpDocProvider->findMethodPhpDoc($declaringClassName, $methodName);
		if ($resolved !== null) {
			return $resolved;
		}
		if (!$this->stubPhpDocProvider->isKnownClass($declaringClassName)) {
			return null;
		}
		if (!$this->reflectionProvider->hasClass($declaringClassName)) {
			return null;
		}

		$ancestors = $this->reflectionProvider->getClass($declaringClassName)->getAncestors();
		foreach ($ancestors as $ancestor) {
			if ($ancestor->getName() === $declaringClassName) {
				continue;
			}
			if (!$ancestor->hasNativeMethod($methodName)) {
				continue;
			}

			$resolved = $this->stubPhpDocProvider->findMethodPhpDoc($ancestor->getName(), $methodName);
			if ($resolved === null) {
				continue;
			}

			return $resolved;
		}

		return null;
	}

}
