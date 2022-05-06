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
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Native\NativeParameterWithPhpDocsReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\FunctionSignature;
use PHPStan\Reflection\SignatureMap\ParameterSignature;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use ReflectionClass;
use ReflectionParameter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_shift;
use function array_slice;
use function class_exists;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function method_exists;
use function reset;
use function sprintf;
use function strtolower;

class PhpClassReflectionExtension
	implements PropertiesClassReflectionExtension, MethodsClassReflectionExtension
{

	/** @var PropertyReflection[][] */
	private array $propertiesIncludingAnnotations = [];

	/** @var PhpPropertyReflection[][] */
	private array $nativeProperties = [];

	/** @var MethodReflection[][] */
	private array $methodsIncludingAnnotations = [];

	/** @var MethodReflection[][] */
	private array $nativeMethods = [];

	/** @var array<string, array<string, Type>> */
	private array $propertyTypesCache = [];

	/** @var array<string, true> */
	private array $inferClassConstructorPropertyTypesInProcess = [];

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
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
		private array $universalObjectCratesClasses,
	)
	{
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
	): PropertyReflection
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

				return new PhpPropertyReflection($declaringClassReflection, null, null, TypeCombinator::union(...$types), $classReflection->getNativeReflection()->getProperty($propertyName), null, false, false);
			}
		}

		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;

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

			if ($hierarchyDistances[$annotationProperty->getDeclaringClass()->getName()] < $hierarchyDistances[$distanceDeclaringClass]) {
				return $annotationProperty;
			}
		}

		$docComment = $propertyReflection->getDocComment() !== false
			? $propertyReflection->getDocComment()
			: null;

		$declaringTraitName = null;
		$phpDocType = null;
		$resolvedPhpDoc = null;
		if ($declaringClassReflection->getFileName() !== null) {
			$declaringTraitName = $this->findPropertyTrait($propertyReflection);
			$constructorName = null;
			if (method_exists($propertyReflection, 'isPromoted') && $propertyReflection->isPromoted()) {
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
		}

		if ($resolvedPhpDoc !== null) {
			$varTags = $resolvedPhpDoc->getVarTags();
			if (isset($varTags[0]) && count($varTags) === 1) {
				$phpDocType = $varTags[0]->getType();
			} elseif (isset($varTags[$propertyName])) {
				$phpDocType = $varTags[$propertyName]->getType();
			}
		}

		if ($phpDocType === null) {
			if (isset($constructorName) && $declaringClassReflection->getFileName() !== null) {
				$constructorDocComment = $declaringClassReflection->getConstructor()->getDocComment();
				$nativeClassReflection = $declaringClassReflection->getNativeReflection();
				$positionalParameterNames = [];
				if ($nativeClassReflection->getConstructor() !== null) {
					$positionalParameterNames = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $nativeClassReflection->getConstructor()->getParameters());
				}
				$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
					$constructorDocComment,
					$declaringClassReflection->getFileName(),
					$declaringClassReflection,
					$declaringTraitName,
					$constructorName,
					$positionalParameterNames,
				);
				$paramTags = $resolvedPhpDoc->getParamTags();
				if (isset($paramTags[$propertyReflection->getName()])) {
					$phpDocType = $paramTags[$propertyReflection->getName()]->getType();
				}
			}
		}

		if ($resolvedPhpDoc !== null) {
			if (!isset($phpDocBlockClassReflection)) {
				throw new ShouldNotHappenException();
			}
			$phpDocType = $phpDocType !== null ? TemplateTypeHelper::resolveTemplateTypes(
				$phpDocType,
				$phpDocBlockClassReflection->getActiveTemplateTypeMap(),
			) : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
		}

		if (
			$phpDocType === null
			&& $this->inferPrivatePropertyTypeFromConstructor
			&& $declaringClassReflection->getFileName() !== null
			&& $propertyReflection->isPrivate()
			&& (!method_exists($propertyReflection, 'isPromoted') || !$propertyReflection->isPromoted())
			&& (!method_exists($propertyReflection, 'hasType') || !$propertyReflection->hasType())
			&& $declaringClassReflection->hasConstructor()
			&& $declaringClassReflection->getConstructor()->getDeclaringClass()->getName() === $declaringClassReflection->getName()
		) {
			$phpDocType = $this->inferPrivatePropertyType(
				$propertyReflection->getName(),
				$declaringClassReflection->getConstructor(),
			);
		}

		$nativeType = null;
		if (method_exists($propertyReflection, 'getType') && $propertyReflection->getType() !== null) {
			$nativeType = $propertyReflection->getType();
		}

		$declaringTrait = null;
		$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
		if (
			$declaringTraitName !== null && $reflectionProvider->hasClass($declaringTraitName)
		) {
			$declaringTrait = $reflectionProvider->getClass($declaringTraitName);
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
			$this->reflectionProviderProvider->getReflectionProvider(),
			$this->universalObjectCratesClasses,
			$classReflection,
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
				$classReflection->getNativeReflection()->getMethod($methodName),
			);
		} else {
			if (
				$methodName !== '__get'
				|| !UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
					$this->reflectionProviderProvider->getReflectionProvider(),
					$this->universalObjectCratesClasses,
					$classReflection,
				)) {
				throw new ShouldNotHappenException();
			}

			$nativeMethodReflection = new FakeBuiltinMethodReflection(
				$methodName,
				$classReflection->getNativeReflection(),
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
		bool $includingAnnotations,
	): MethodReflection
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

			if ($hierarchyDistances[$annotationMethod->getDeclaringClass()->getName()] < $hierarchyDistances[$distanceDeclaringClass]) {
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
			$variantNumbers = [0];
			$i = 1;
			while ($this->signatureMapProvider->hasMethodSignature($declaringClassName, $methodReflection->getName(), $i)) {
				$variantNumbers[] = $i;
				$i++;
			}

			$variants = [];
			$reflectionMethod = null;
			$throwType = null;
			if ($classReflection->getNativeReflection()->hasMethod($methodReflection->getName())) {
				$reflectionMethod = $classReflection->getNativeReflection()->getMethod($methodReflection->getName());
			} elseif (class_exists($classReflection->getName(), false)) {
				$reflectionClass = new ReflectionClass($classReflection->getName());
				if ($reflectionClass->hasMethod($methodReflection->getName())) {
					$reflectionMethod = $reflectionClass->getMethod($methodReflection->getName());
				}
			}
			foreach ($variantNumbers as $variantNumber) {
				$methodSignature = $this->signatureMapProvider->getMethodSignature($declaringClassName, $methodReflection->getName(), $reflectionMethod, $variantNumber);
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
				if (count($variantNumbers) === 1) {
					$stubPhpDocPair = $this->findMethodPhpDocIncludingAncestors($declaringClass, $methodReflection->getName(), array_map(static fn (ParameterSignature $parameterSignature): string => $parameterSignature->getName(), $methodSignature->getParameters()));
					if ($stubPhpDocPair !== null) {
						[$stubPhpDoc, $stubDeclaringClass] = $stubPhpDocPair;
						$templateTypeMap = $stubDeclaringClass->getActiveTemplateTypeMap();
						$returnTag = $stubPhpDoc->getReturnTag();
						if ($returnTag !== null) {
							$stubPhpDocReturnType = TemplateTypeHelper::resolveTemplateTypes(
								$returnTag->getType(),
								$templateTypeMap,
							);
						}

						foreach ($stubPhpDoc->getParamTags() as $name => $paramTag) {
							$stubPhpDocParameterTypes[$name] = TemplateTypeHelper::resolveTemplateTypes(
								$paramTag->getType(),
								$templateTypeMap,
							);
							$stubPhpDocParameterVariadicity[$name] = $paramTag->isVariadic();
						}

						$throwsTag = $stubPhpDoc->getThrowsTag();
						if ($throwsTag !== null) {
							$throwType = $throwsTag->getType();
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
						if ($returnTag !== null) {
							$phpDocReturnType = $returnTag->getType();
						}
						foreach ($phpDocBlock->getParamTags() as $name => $paramTag) {
							$phpDocParameterTypes[$name] = $paramTag->getType();
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
				$variants[] = $this->createNativeMethodVariant($methodSignature, $stubPhpDocParameterTypes, $stubPhpDocParameterVariadicity, $stubPhpDocReturnType, $phpDocParameterTypes, $phpDocReturnType, $phpDocParameterNameMapping);
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
				$variants,
				$hasSideEffects,
				$throwType,
			);
		}

		$declaringTraitName = $this->findMethodTrait($methodReflection);
		$resolvedPhpDoc = null;
		$stubPhpDocPair = $this->findMethodPhpDocIncludingAncestors($declaringClass, $methodReflection->getName(), array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters()));
		$phpDocBlockClassReflection = $declaringClass;
		if ($stubPhpDocPair !== null) {
			[$resolvedPhpDoc, $phpDocBlockClassReflection] = $stubPhpDocPair;
		}

		if ($resolvedPhpDoc === null) {
			if ($declaringClass->getFileName() !== null) {
				$docComment = $methodReflection->getDocComment();
				$positionalParameterNames = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $methodReflection->getParameters());

				$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
					$docComment,
					$declaringClass->getFileName(),
					$declaringClass,
					$declaringTraitName,
					$methodReflection->getName(),
					$positionalParameterNames,
				);
				$phpDocBlockClassReflection = $declaringClass;
			}
		}

		$declaringTrait = null;
		$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
		if (
			$declaringTraitName !== null && $reflectionProvider->hasClass($declaringTraitName)
		) {
			$declaringTrait = $reflectionProvider->getClass($declaringTraitName);
		}

		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTypes = [];
		$phpDocReturnType = null;
		$phpDocThrowType = null;
		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		$isPure = false;
		if (
			$methodReflection instanceof NativeBuiltinMethodReflection
			&& $methodReflection->isConstructor()
			&& $declaringClass->getFileName() !== null
		) {
			foreach ($methodReflection->getParameters() as $parameter) {
				if (!method_exists($parameter, 'isPromoted') || !$parameter->isPromoted()) {
					continue;
				}

				if (!$methodReflection->getDeclaringClass()->hasProperty($parameter->getName())) {
					continue;
				}

				$parameterProperty = $methodReflection->getDeclaringClass()->getProperty($parameter->getName());
				if (!method_exists($parameterProperty, 'isPromoted') || !$parameterProperty->isPromoted()) {
					continue;
				}
				if ($parameterProperty->getDocComment() === false) {
					continue;
				}

				$propertyDocblock = $this->fileTypeMapper->getResolvedPhpDoc(
					$declaringClass->getFileName(),
					$declaringClassName,
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
		if ($resolvedPhpDoc !== null) {
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
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
				);
			}
			$nativeReturnType = TypehintHelper::decideTypeFromReflection(
				$methodReflection->getReturnType(),
				null,
				$declaringClass->getName(),
			);
			$phpDocReturnType = $this->getPhpDocReturnType($phpDocBlockClassReflection, $resolvedPhpDoc, $nativeReturnType);
			$phpDocThrowType = $resolvedPhpDoc->getThrowsTag() !== null ? $resolvedPhpDoc->getThrowsTag()->getType() : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$isFinal = $resolvedPhpDoc->isFinal();
			$isPure = $resolvedPhpDoc->isPure();
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
			$isPure,
		);
	}

	/**
	 * @param array<string, Type> $stubPhpDocParameterTypes
	 * @param array<string, bool> $stubPhpDocParameterVariadicity
	 * @param array<string, Type> $phpDocParameterTypes
	 * @param array<string, string> $phpDocParameterNameMapping
	 */
	private function createNativeMethodVariant(
		FunctionSignature $methodSignature,
		array $stubPhpDocParameterTypes,
		array $stubPhpDocParameterVariadicity,
		?Type $stubPhpDocReturnType,
		array $phpDocParameterTypes,
		?Type $phpDocReturnType,
		array $phpDocParameterNameMapping,
	): FunctionVariantWithPhpDocs
	{
		$parameters = [];
		foreach ($methodSignature->getParameters() as $parameterSignature) {
			$type = null;
			$phpDocType = null;

			$phpDocParameterName = $phpDocParameterNameMapping[$parameterSignature->getName()] ?? $parameterSignature->getName();

			if (isset($stubPhpDocParameterTypes[$parameterSignature->getName()])) {
				$type = $stubPhpDocParameterTypes[$parameterSignature->getName()];
				$phpDocType = $stubPhpDocParameterTypes[$parameterSignature->getName()];
			} elseif (isset($phpDocParameterTypes[$phpDocParameterName])) {
				$phpDocType = $phpDocParameterTypes[$phpDocParameterName];
			}

			$parameters[] = new NativeParameterWithPhpDocsReflection(
				$phpDocParameterName,
				$parameterSignature->isOptional(),
				$type ?? $parameterSignature->getType(),
				$phpDocType ?? new MixedType(),
				$parameterSignature->getNativeType(),
				$parameterSignature->passedByReference(),
				$stubPhpDocParameterVariadicity[$parameterSignature->getName()] ?? $parameterSignature->isVariadic(),
				null,
			);
		}

		$returnType = null;
		if ($stubPhpDocReturnType !== null) {
			$returnType = $stubPhpDocReturnType;
			$phpDocReturnType = $stubPhpDocReturnType;
		}

		return new FunctionVariantWithPhpDocs(
			TemplateTypeMap::createEmpty(),
			null,
			$parameters,
			$methodSignature->isVariadic(),
			$returnType ?? $methodSignature->getReturnType(),
			$phpDocReturnType ?? new MixedType(),
			$methodSignature->getNativeReturnType(),
		);
	}

	private function findPropertyTrait(\ReflectionProperty $propertyReflection): ?string
	{
		if ($propertyReflection instanceof ReflectionProperty) {
			$declaringClass = $propertyReflection->getBetterReflection()->getDeclaringClass();
			if ($declaringClass->isTrait()) {
				if ($propertyReflection->getDeclaringClass()->isTrait() && $propertyReflection->getDeclaringClass()->getName() === $declaringClass->getName()) {
					return null;
				}

				return $declaringClass->getName();
			}

			return null;
		}
		$declaringClass = $propertyReflection->getDeclaringClass();
		$trait = $this->deepScanTraitsForProperty($declaringClass->getTraits(), $propertyReflection);
		if ($trait !== null) {
			return $trait;
		}

		return null;
	}

	/**
	 * @param ReflectionClass<object>[] $traits
	 */
	private function deepScanTraitsForProperty(
		array $traits,
		\ReflectionProperty $propertyReflection,
	): ?string
	{
		foreach ($traits as $trait) {
			$result = $this->deepScanTraitsForProperty($trait->getTraits(), $propertyReflection);
			if ($result !== null) {
				return $result;
			}

			if (!$trait->hasProperty($propertyReflection->getName())) {
				continue;
			}

			$traitProperty = $trait->getProperty($propertyReflection->getName());
			if ($traitProperty->getDocComment() === $propertyReflection->getDocComment()) {
				return $trait->getName();
			}
		}

		return null;
	}

	private function findMethodTrait(
		BuiltinMethodReflection $methodReflection,
	): ?string
	{
		if ($methodReflection->getReflection() instanceof ReflectionMethod) {
			$declaringClass = $methodReflection->getReflection()->getBetterReflection()->getDeclaringClass();
			if ($declaringClass->isTrait()) {
				if ($methodReflection->getDeclaringClass()->isTrait() && $declaringClass->getName() === $methodReflection->getDeclaringClass()->getName()) {
					return null;
				}

				return $declaringClass->getName();
			}

			return null;
		}

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
	 * @return ReflectionClass[]
	 */
	private function collectTraits(ReflectionClass $class): array
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

		$classScope = $this->scopeFactory->create(
			ScopeContext::create($fileName),
			false,
			[],
			$constructor,
			$namespace,
		)->enterClass($declaringClass);
		[$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure] = $this->nodeScopeResolver->getPhpDocs($classScope, $methodNode);
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
			if ($propertyType instanceof ConstantArrayType) {
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
	private function findMethodPhpDocIncludingAncestors(ClassReflection $declaringClass, string $methodName, array $positionalParameterNames): ?array
	{
		$declaringClassName = $declaringClass->getName();
		$resolved = $this->stubPhpDocProvider->findMethodPhpDoc($declaringClassName, $methodName, $positionalParameterNames);
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

			$resolved = $this->stubPhpDocProvider->findMethodPhpDoc($ancestor->getName(), $methodName, $positionalParameterNames);
			if ($resolved === null) {
				continue;
			}

			return [$resolved, $ancestor];
		}

		return null;
	}

}
