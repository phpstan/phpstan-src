<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\PhpDoc\Tag\DeprecatedTag;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\ParamClosureThisTag;
use PHPStan\PhpDoc\Tag\ParamOutTag;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\PhpDoc\Tag\RequireImplementsTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDoc\Tag\SealedTypeTag;
use PHPStan\PhpDoc\Tag\SelfOutTypeTag;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\Tag\ThrowsTag;
use PHPStan\PhpDoc\Tag\TypeAliasImportTag;
use PHPStan\PhpDoc\Tag\TypeAliasTag;
use PHPStan\PhpDoc\Tag\TypedTag;
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function array_map;
use function count;
use function is_bool;
use function substr;

/**
 * @api
 */
final class ResolvedPhpDocBlock
{

	public const EMPTY_DOC_STRING = '/** */';

	private PhpDocNode $phpDocNode;

	/** @var PhpDocNode[] */
	private array $phpDocNodes;

	private string $phpDocString;

	private ?string $filename;

	private ?NameScope $nameScope = null;

	private TemplateTypeMap $templateTypeMap;

	/** @var array<string, TemplateTag> */
	private array $templateTags;

	private PhpDocNodeResolver $phpDocNodeResolver;

	private ReflectionProvider $reflectionProvider;

	/** @var array<(string|int), VarTag>|false */
	private array|false $varTags = false;

	/** @var array<string, MethodTag>|false */
	private array|false $methodTags = false;

	/** @var array<string, PropertyTag>|false */
	private array|false $propertyTags = false;

	/** @var array<string, ExtendsTag>|false */
	private array|false $extendsTags = false;

	/** @var array<string, ImplementsTag>|false */
	private array|false $implementsTags = false;

	/** @var array<string, UsesTag>|false */
	private array|false $usesTags = false;

	/** @var array<string, ParamTag>|false */
	private array|false $paramTags = false;

	/** @var array<string, ParamOutTag>|false */
	private array|false $paramOutTags = false;

	/** @var array<string, bool>|false */
	private array|false $paramsImmediatelyInvokedCallable = false;

	/** @var array<string, bool>|false */
	private array|false $paramsPureUnlessCallableIsImpure = false;

	/** @var array<string, bool>|false */
	private array|false $paramsPureUnlessParameterPassed = false;

	/** @var array<string, ParamClosureThisTag>|false */
	private array|false $paramClosureThisTags = false;

	private ReturnTag|false|null $returnTag = false;

	private ThrowsTag|false|null $throwsTag = false;

	/** @var array<MixinTag>|false */
	private array|false $mixinTags = false;

	/** @var array<RequireExtendsTag>|false */
	private array|false $requireExtendsTags = false;

	/** @var array<RequireImplementsTag>|false */
	private array|false $requireImplementsTags = false;

	/** @var array<SealedTypeTag>|false */
	private array|false $sealedTypeTags = false;

	/** @var array<TypeAliasTag>|false */
	private array|false $typeAliasTags = false;

	/** @var array<TypeAliasImportTag>|false */
	private array|false $typeAliasImportTags = false;

	/** @var array<AssertTag>|false */
	private array|false $assertTags = false;

	private SelfOutTypeTag|false|null $selfOutTypeTag = false;

	private DeprecatedTag|false|null $deprecatedTag = false;

	private ?bool $isDeprecated = null;

	private ?bool $isNotDeprecated = null;

	private ?bool $isInternal = null;

	private ?bool $isFinal = null;

	/** @var bool|'notLoaded'|null */
	private bool|string|null $isPure = 'notLoaded';

	private ?bool $areAllMethodsPure = null;

	private ?bool $areAllMethodsImpure = null;

	private ?bool $isReadOnly = null;

	private ?bool $isImmutable = null;

	private ?bool $isAllowedPrivateMutation = null;

	private ?bool $hasConsistentConstructor = null;

	private ?bool $acceptsNamedArguments = null;

	private function __construct()
	{
	}

	/**
	 * @param TemplateTag[] $templateTags
	 */
	public static function create(
		PhpDocNode $phpDocNode,
		string $phpDocString,
		?string $filename,
		NameScope $nameScope,
		TemplateTypeMap $templateTypeMap,
		array $templateTags,
		PhpDocNodeResolver $phpDocNodeResolver,
		ReflectionProvider $reflectionProvider,
	): self
	{
		// new property also needs to be added to withNameScope(), createEmpty() and merge()
		$self = new self();
		$self->phpDocNode = $phpDocNode;
		$self->phpDocNodes = [$phpDocNode];
		$self->phpDocString = $phpDocString;
		$self->filename = $filename;
		$self->nameScope = $nameScope;
		$self->templateTypeMap = $templateTypeMap;
		$self->templateTags = $templateTags;
		$self->phpDocNodeResolver = $phpDocNodeResolver;
		$self->reflectionProvider = $reflectionProvider;

		return $self;
	}

	public function withNameScope(NameScope $nameScope): self
	{
		$self = new self();
		$self->phpDocNode = $this->phpDocNode;
		$self->phpDocNodes = $this->phpDocNodes;
		$self->phpDocString = $this->phpDocString;
		$self->filename = $this->filename;
		$self->nameScope = $nameScope;
		$self->templateTypeMap = $this->templateTypeMap;
		$self->templateTags = $this->templateTags;
		$self->phpDocNodeResolver = $this->phpDocNodeResolver;
		$self->reflectionProvider = $this->reflectionProvider;

		return $self;
	}

	public static function createEmpty(): self
	{
		// new property also needs to be added to merge()
		$self = new self();
		$self->phpDocString = self::EMPTY_DOC_STRING;
		$self->phpDocNodes = [];
		$self->filename = null;
		$self->templateTypeMap = TemplateTypeMap::createEmpty();
		$self->templateTags = [];
		$self->varTags = [];
		$self->methodTags = [];
		$self->propertyTags = [];
		$self->extendsTags = [];
		$self->implementsTags = [];
		$self->usesTags = [];
		$self->paramTags = [];
		$self->paramOutTags = [];
		$self->paramsImmediatelyInvokedCallable = [];
		$self->paramsPureUnlessCallableIsImpure = [];
		$self->paramsPureUnlessParameterPassed = [];
		$self->paramClosureThisTags = [];
		$self->returnTag = null;
		$self->throwsTag = null;
		$self->mixinTags = [];
		$self->requireExtendsTags = [];
		$self->requireImplementsTags = [];
		$self->sealedTypeTags = [];
		$self->typeAliasTags = [];
		$self->typeAliasImportTags = [];
		$self->assertTags = [];
		$self->selfOutTypeTag = null;
		$self->deprecatedTag = null;
		$self->isDeprecated = false;
		$self->isNotDeprecated = false;
		$self->isInternal = false;
		$self->isFinal = false;
		$self->isPure = null;
		$self->areAllMethodsPure = false;
		$self->areAllMethodsImpure = false;
		$self->isReadOnly = false;
		$self->isImmutable = false;
		$self->isAllowedPrivateMutation = false;
		$self->hasConsistentConstructor = false;
		$self->acceptsNamedArguments = true;

		return $self;
	}

	public function merge(ResolvedPhpDocBlock $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $declaringClass, ClassReflection $parentClass): self
	{
		// new property also needs to be added to createEmpty()
		$result = new self();
		// we will resolve everything on $this here so these properties don't have to be populated
		// skip $result->phpDocNode
		$phpDocNodes = $this->phpDocNodes;
		$acceptsNamedArguments = $this->acceptsNamedArguments();
		foreach ($parent->phpDocNodes as $phpDocNode) {
			$phpDocNodes[] = $phpDocNode;
			$acceptsNamedArguments = $acceptsNamedArguments && $parent->acceptsNamedArguments();
		}
		$result->phpDocNodes = $phpDocNodes;
		$result->phpDocString = $this->phpDocString;
		$result->filename = $this->filename;
		// skip $result->nameScope
		$result->templateTypeMap = $this->templateTypeMap;
		$result->templateTags = $this->templateTags;
		// skip $result->phpDocNodeResolver
		$result->varTags = self::mergeVarTags($this->getVarTags(), $parent, $parentClass);
		$result->methodTags = $this->getMethodTags();
		$result->propertyTags = $this->getPropertyTags();
		$result->extendsTags = $this->getExtendsTags();
		$result->implementsTags = $this->getImplementsTags();
		$result->usesTags = $this->getUsesTags();
		$result->paramTags = self::mergeParamTags($this->getParamTags(), $parent, $parameterMapping, $parentClass);
		$result->paramOutTags = self::mergeParamOutTags($this->getParamOutTags(), $parent, $parameterMapping, $parentClass);
		$result->paramsImmediatelyInvokedCallable = self::mergeParamsImmediatelyInvokedCallable($this->getParamsImmediatelyInvokedCallable(), $parent, $parameterMapping);
		$result->paramsPureUnlessCallableIsImpure = self::mergeParamsPureUnlessCallableIsImpure($this->getParamsPureUnlessCallableIsImpure(), $parent, $parameterMapping);
		$result->paramsPureUnlessParameterPassed = self::mergeParamsPureUnlessParameterPassed($this->getParamsPureUnlessParameterPassed(), $parent, $parameterMapping);
		$result->paramClosureThisTags = self::mergeParamClosureThisTags($this->getParamClosureThisTags(), $parent, $parameterMapping, $parentClass);
		$result->returnTag = self::mergeReturnTags($this->getReturnTag(), $declaringClass, $parent, $parameterMapping, $parentClass);
		$result->throwsTag = self::mergeThrowsTags($this->getThrowsTag(), $parent);
		$result->mixinTags = $this->getMixinTags();
		$result->requireExtendsTags = $this->getRequireExtendsTags();
		$result->requireImplementsTags = $this->getRequireImplementsTags();
		$result->sealedTypeTags = $this->getSealedTags();
		$result->typeAliasTags = $this->getTypeAliasTags();
		$result->typeAliasImportTags = $this->getTypeAliasImportTags();
		$result->assertTags = self::mergeAssertTags($this->getAssertTags(), $parent, $parameterMapping, $parentClass);
		$result->selfOutTypeTag = self::mergeSelfOutTypeTags($this->getSelfOutTag(), $parent);
		$result->deprecatedTag = self::mergeDeprecatedTags($this->getDeprecatedTag(), $this->isNotDeprecated(), $parent);
		$result->isDeprecated = $result->deprecatedTag !== null;
		$result->isNotDeprecated = $this->isNotDeprecated();
		$result->isInternal = $this->isInternal();
		$result->isFinal = $this->isFinal();
		$result->isPure = self::mergePureTags($this->isPure(), $parent);
		$result->areAllMethodsPure = $this->areAllMethodsPure();
		$result->areAllMethodsImpure = $this->areAllMethodsImpure();
		$result->isReadOnly = $this->isReadOnly();
		$result->isImmutable = $this->isImmutable();
		$result->isAllowedPrivateMutation = $this->isAllowedPrivateMutation();
		$result->hasConsistentConstructor = $this->hasConsistentConstructor();
		$result->acceptsNamedArguments = $acceptsNamedArguments;

		return $result;
	}

	/**
	 * @param array<string, string> $parameterNameMapping
	 */
	public function changeParameterNamesByMapping(array $parameterNameMapping): self
	{
		if (count($this->phpDocNodes) === 0) {
			return $this;
		}

		$mapParameterCb = static function (Type $type, callable $traverse) use ($parameterNameMapping): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$parameterName = substr($type->getParameterName(), 1);
				if (array_key_exists($parameterName, $parameterNameMapping)) {
					$type = $type->changeParameterName('$' . $parameterNameMapping[$parameterName]);
				}
			}

			return $traverse($type);
		};

		$newParamTags = [];
		foreach ($this->getParamTags() as $key => $paramTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}
			$transformedType = TypeTraverser::map($paramTag->getType(), $mapParameterCb);
			$newParamTags[$parameterNameMapping[$key]] = $paramTag->withType($transformedType);
		}

		$newParamOutTags = [];
		foreach ($this->getParamOutTags() as $key => $paramOutTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}

			$transformedType = TypeTraverser::map($paramOutTag->getType(), $mapParameterCb);
			$newParamOutTags[$parameterNameMapping[$key]] = $paramOutTag->withType($transformedType);
		}

		$newParamsImmediatelyInvokedCallable = [];
		foreach ($this->getParamsImmediatelyInvokedCallable() as $key => $immediatelyInvokedCallable) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}

			$newParamsImmediatelyInvokedCallable[$parameterNameMapping[$key]] = $immediatelyInvokedCallable;
		}

		$paramClosureThisTags = $this->getParamClosureThisTags();
		$newParamClosureThisTags = [];
		foreach ($paramClosureThisTags as $key => $paramClosureThisTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}

			$transformedType = TypeTraverser::map($paramClosureThisTag->getType(), $mapParameterCb);
			$newParamClosureThisTags[$parameterNameMapping[$key]] = $paramClosureThisTag->withType($transformedType);
		}

		$returnTag = $this->getReturnTag();
		if ($returnTag !== null) {
			$transformedType = TypeTraverser::map($returnTag->getType(), $mapParameterCb);
			$returnTag = $returnTag->withType($transformedType);
		}

		$assertTags = $this->getAssertTags();
		if (count($assertTags) > 0) {
			$assertTags = array_map(static function (AssertTag $tag) use ($parameterNameMapping): AssertTag {
				$parameterName = substr($tag->getParameter()->getParameterName(), 1);
				if (array_key_exists($parameterName, $parameterNameMapping)) {
					$tag = $tag->withParameter($tag->getParameter()->changeParameterName('$' . $parameterNameMapping[$parameterName]));
				}
				return $tag;
			}, $assertTags);
		}

		$self = new self();
		$self->phpDocNode = $this->phpDocNode;
		$self->phpDocNodes = $this->phpDocNodes;
		$self->phpDocString = $this->phpDocString;
		$self->filename = $this->filename;
		$self->nameScope = $this->nameScope;
		$self->templateTypeMap = $this->templateTypeMap;
		$self->templateTags = $this->templateTags;
		$self->phpDocNodeResolver = $this->phpDocNodeResolver;
		$self->reflectionProvider = $this->reflectionProvider;
		$self->varTags = $this->varTags;
		$self->methodTags = $this->methodTags;
		$self->propertyTags = $this->propertyTags;
		$self->extendsTags = $this->extendsTags;
		$self->implementsTags = $this->implementsTags;
		$self->usesTags = $this->usesTags;
		$self->paramTags = $newParamTags;
		$self->paramOutTags = $newParamOutTags;
		$self->paramsImmediatelyInvokedCallable = $newParamsImmediatelyInvokedCallable;
		$self->paramClosureThisTags = $newParamClosureThisTags;
		$self->returnTag = $returnTag;
		$self->throwsTag = $this->throwsTag;
		$self->mixinTags = $this->mixinTags;
		$self->requireImplementsTags = $this->requireImplementsTags;
		$self->requireExtendsTags = $this->requireExtendsTags;
		$self->typeAliasTags = $this->typeAliasTags;
		$self->typeAliasImportTags = $this->typeAliasImportTags;
		$self->assertTags = $assertTags;
		$self->selfOutTypeTag = $this->selfOutTypeTag;
		$self->deprecatedTag = $this->deprecatedTag;
		$self->isDeprecated = $this->isDeprecated;
		$self->isNotDeprecated = $this->isNotDeprecated;
		$self->isInternal = $this->isInternal;
		$self->isFinal = $this->isFinal;
		$self->isPure = $this->isPure;

		return $self;
	}

	public function hasPhpDocString(): bool
	{
		return $this->phpDocString !== self::EMPTY_DOC_STRING;
	}

	public function getPhpDocString(): string
	{
		return $this->phpDocString;
	}

	/**
	 * @return PhpDocNode[]
	 */
	public function getPhpDocNodes(): array
	{
		return $this->phpDocNodes;
	}

	public function getFilename(): ?string
	{
		return $this->filename;
	}

	private function getNameScope(): NameScope
	{
		if ($this->nameScope === null) {
			throw new ShouldNotHappenException();
		}

		return $this->nameScope;
	}

	public function getNullableNameScope(): ?NameScope
	{
		return $this->nameScope;
	}

	/**
	 * @return array<(string|int), VarTag>
	 */
	public function getVarTags(): array
	{
		if ($this->varTags === false) {
			$this->varTags = $this->phpDocNodeResolver->resolveVarTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->varTags;
	}

	/**
	 * @return array<string, MethodTag>
	 */
	public function getMethodTags(): array
	{
		if ($this->methodTags === false) {
			$this->methodTags = $this->phpDocNodeResolver->resolveMethodTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->methodTags;
	}

	/**
	 * @return array<string, PropertyTag>
	 */
	public function getPropertyTags(): array
	{
		if ($this->propertyTags === false) {
			$this->propertyTags = $this->phpDocNodeResolver->resolvePropertyTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->propertyTags;
	}

	/**
	 * @return array<string, TemplateTag>
	 */
	public function getTemplateTags(): array
	{
		return $this->templateTags;
	}

	/**
	 * @return array<string, ExtendsTag>
	 */
	public function getExtendsTags(): array
	{
		if ($this->extendsTags === false) {
			$this->extendsTags = $this->phpDocNodeResolver->resolveExtendsTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->extendsTags;
	}

	/**
	 * @return array<string, ImplementsTag>
	 */
	public function getImplementsTags(): array
	{
		if ($this->implementsTags === false) {
			$this->implementsTags = $this->phpDocNodeResolver->resolveImplementsTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->implementsTags;
	}

	/**
	 * @return array<string, UsesTag>
	 */
	public function getUsesTags(): array
	{
		if ($this->usesTags === false) {
			$this->usesTags = $this->phpDocNodeResolver->resolveUsesTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->usesTags;
	}

	/**
	 * @return array<string, ParamTag>
	 */
	public function getParamTags(): array
	{
		if ($this->paramTags === false) {
			$this->paramTags = $this->phpDocNodeResolver->resolveParamTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->paramTags;
	}

	/**
	 * @return array<string, ParamOutTag>
	 */
	public function getParamOutTags(): array
	{
		if ($this->paramOutTags === false) {
			$this->paramOutTags = $this->phpDocNodeResolver->resolveParamOutTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->paramOutTags;
	}

	/**
	 * @return array<string, bool>
	 */
	public function getParamsImmediatelyInvokedCallable(): array
	{
		if ($this->paramsImmediatelyInvokedCallable === false) {
			$this->paramsImmediatelyInvokedCallable = $this->phpDocNodeResolver->resolveParamImmediatelyInvokedCallable($this->phpDocNode);
		}

		return $this->paramsImmediatelyInvokedCallable;
	}

	/**
	 * @return array<string, bool>
	 */
	public function getParamsPureUnlessCallableIsImpure(): array
	{
		if ($this->paramsPureUnlessCallableIsImpure === false) {
			$this->paramsPureUnlessCallableIsImpure = $this->phpDocNodeResolver->resolveParamPureUnlessCallableIsImpure($this->phpDocNode);
		}

		return $this->paramsPureUnlessCallableIsImpure;
	}

	/**
	 * @return array<string, bool>
	 */
	public function getParamsPureUnlessParameterPassed(): array
	{
		if ($this->paramsPureUnlessParameterPassed === false) {
			$this->paramsPureUnlessParameterPassed = $this->phpDocNodeResolver->resolveParamPureUnlessParameterPassed($this->phpDocNode);
		}

		return $this->paramsPureUnlessParameterPassed;
	}

	/**
	 * @return array<string, ParamClosureThisTag>
	 */
	public function getParamClosureThisTags(): array
	{
		if ($this->paramClosureThisTags === false) {
			$this->paramClosureThisTags = $this->phpDocNodeResolver->resolveParamClosureThisTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->paramClosureThisTags;
	}

	public function getReturnTag(): ?ReturnTag
	{
		if (is_bool($this->returnTag)) {
			$this->returnTag = $this->phpDocNodeResolver->resolveReturnTag(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->returnTag;
	}

	public function getThrowsTag(): ?ThrowsTag
	{
		if (is_bool($this->throwsTag)) {
			$this->throwsTag = $this->phpDocNodeResolver->resolveThrowsTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->throwsTag;
	}

	/**
	 * @return array<MixinTag>
	 */
	public function getMixinTags(): array
	{
		if ($this->mixinTags === false) {
			$this->mixinTags = $this->phpDocNodeResolver->resolveMixinTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->mixinTags;
	}

	/**
	 * @return array<RequireExtendsTag>
	 */
	public function getRequireExtendsTags(): array
	{
		if ($this->requireExtendsTags === false) {
			$this->requireExtendsTags = $this->phpDocNodeResolver->resolveRequireExtendsTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->requireExtendsTags;
	}

	/**
	 * @return array<RequireImplementsTag>
	 */
	public function getRequireImplementsTags(): array
	{
		if ($this->requireImplementsTags === false) {
			$this->requireImplementsTags = $this->phpDocNodeResolver->resolveRequireImplementsTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->requireImplementsTags;
	}

	/**
	 * @return array<SealedTypeTag>
	 */
	public function getSealedTags(): array
	{
		if ($this->sealedTypeTags === false) {
			$this->sealedTypeTags = $this->phpDocNodeResolver->resolveSealedTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->sealedTypeTags;
	}

	/**
	 * @return array<TypeAliasTag>
	 */
	public function getTypeAliasTags(): array
	{
		if ($this->typeAliasTags === false) {
			$this->typeAliasTags = $this->phpDocNodeResolver->resolveTypeAliasTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->typeAliasTags;
	}

	/**
	 * @return array<TypeAliasImportTag>
	 */
	public function getTypeAliasImportTags(): array
	{
		if ($this->typeAliasImportTags === false) {
			$this->typeAliasImportTags = $this->phpDocNodeResolver->resolveTypeAliasImportTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->typeAliasImportTags;
	}

	/**
	 * @return array<AssertTag>
	 */
	public function getAssertTags(): array
	{
		if ($this->assertTags === false) {
			$this->assertTags = $this->phpDocNodeResolver->resolveAssertTags(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->assertTags;
	}

	public function getSelfOutTag(): ?SelfOutTypeTag
	{
		if ($this->selfOutTypeTag === false) {
			$this->selfOutTypeTag = $this->phpDocNodeResolver->resolveSelfOutTypeTag(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}

		return $this->selfOutTypeTag;
	}

	public function getDeprecatedTag(): ?DeprecatedTag
	{
		if (is_bool($this->deprecatedTag)) {
			$this->deprecatedTag = $this->phpDocNodeResolver->resolveDeprecatedTag(
				$this->phpDocNode,
				$this->getNameScope(),
			);
		}
		return $this->deprecatedTag;
	}

	public function isDeprecated(): bool
	{
		return $this->isDeprecated ??= $this->phpDocNodeResolver->resolveIsDeprecated(
			$this->phpDocNode,
		);
	}

	/**
	 * @internal
	 */
	public function isNotDeprecated(): bool
	{
		return $this->isNotDeprecated ??= $this->phpDocNodeResolver->resolveIsNotDeprecated(
			$this->phpDocNode,
		);
	}

	public function isInternal(): bool
	{
		return $this->isInternal ??= $this->phpDocNodeResolver->resolveIsInternal(
			$this->phpDocNode,
		);
	}

	public function isFinal(): bool
	{
		return $this->isFinal ??= $this->phpDocNodeResolver->resolveIsFinal(
			$this->phpDocNode,
		);
	}

	public function hasConsistentConstructor(): bool
	{
		return $this->hasConsistentConstructor ??= $this->phpDocNodeResolver->resolveHasConsistentConstructor(
			$this->phpDocNode,
		);
	}

	public function acceptsNamedArguments(): bool
	{
		return $this->acceptsNamedArguments ??= $this->phpDocNodeResolver->resolveAcceptsNamedArguments(
			$this->phpDocNode,
		);
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	public function isPure(): ?bool
	{
		if ($this->isPure === 'notLoaded') {
			$pure = $this->phpDocNodeResolver->resolveIsPure(
				$this->phpDocNode,
			);
			if ($pure) {
				$this->isPure = true;
				return $this->isPure;
			}

			$impure = $this->phpDocNodeResolver->resolveIsImpure(
				$this->phpDocNode,
			);
			if ($impure) {
				$this->isPure = false;
				return $this->isPure;
			}

			$this->isPure = null;
		}

		return $this->isPure;
	}

	public function areAllMethodsPure(): bool
	{
		return $this->areAllMethodsPure ??= $this->phpDocNodeResolver->resolveAllMethodsPure(
			$this->phpDocNode,
		);
	}

	public function areAllMethodsImpure(): bool
	{
		return $this->areAllMethodsImpure ??= $this->phpDocNodeResolver->resolveAllMethodsImpure(
			$this->phpDocNode,
		);
	}

	public function isReadOnly(): bool
	{
		return $this->isReadOnly ??= $this->phpDocNodeResolver->resolveIsReadOnly(
			$this->phpDocNode,
		);
	}

	public function isImmutable(): bool
	{
		return $this->isImmutable ??= $this->phpDocNodeResolver->resolveIsImmutable(
			$this->phpDocNode,
		);
	}

	public function isAllowedPrivateMutation(): bool
	{
		return $this->isAllowedPrivateMutation ??= $this->phpDocNodeResolver->resolveAllowPrivateMutation(
			$this->phpDocNode,
		);
	}

	/**
	 * @param array<string|int, VarTag> $varTags
	 * @return array<string|int, VarTag>
	 */
	private static function mergeVarTags(array $varTags, self $parent, ClassReflection $parentClass): array
	{
		// Only allow one var tag per comment. Check the parent if child does not have this tag.
		if (count($varTags) > 0) {
			return $varTags;
		}

		$result = self::mergeOneParentVarTags($parent, $parentClass);
		if ($result === null) {
			return [];
		}

		return $result;
	}

	/**
	 * @return array<string|int, VarTag>|null
	 */
	private static function mergeOneParentVarTags(self $parent, ClassReflection $parentClass): ?array
	{
		foreach ($parent->getVarTags() as $key => $parentVarTag) {
			return [$key => self::resolveTemplateTypeInTag($parentVarTag->toImplicit(), $parentClass, TemplateTypeVariance::createInvariant())];
		}

		return null;
	}

	/**
	 * @param array<string, ParamTag> $paramTags
	 * @return array<string, ParamTag>
	 */
	private static function mergeParamTags(array $paramTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		return self::mergeOneParentParamTags($paramTags, $parent, $parameterMapping, $parentClass);
	}

	/**
	 * @param array<string, ParamTag> $paramTags
	 * @return array<string, ParamTag>
	 */
	private static function mergeOneParentParamTags(array $paramTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		$parentParamTags = $parameterMapping->transformArrayKeysWithParameterNameMapping($parent->getParamTags());

		foreach ($parentParamTags as $name => $parentParamTag) {
			if (array_key_exists($name, $paramTags)) {
				continue;
			}

			$paramTags[$name] = self::resolveTemplateTypeInTag(
				$parentParamTag->withType($parameterMapping->transformConditionalReturnTypeWithParameterNameMapping($parentParamTag->getType())),
				$parentClass,
				TemplateTypeVariance::createContravariant(),
			);
		}

		return $paramTags;
	}

	private static function mergeReturnTags(?ReturnTag $returnTag, ClassReflection $classReflection, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): ?ReturnTag
	{
		if ($returnTag !== null) {
			return $returnTag;
		}

		return self::mergeOneParentReturnTag($returnTag, $classReflection, $parent, $parameterMapping, $parentClass);
	}

	private static function mergeOneParentReturnTag(?ReturnTag $returnTag, ClassReflection $classReflection, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): ?ReturnTag
	{
		$parentReturnTag = $parent->getReturnTag();
		if ($parentReturnTag === null) {
			return $returnTag;
		}

		$parentType = $parentReturnTag->getType();
		$parentType = TypeTraverser::map(
			$parentType,
			static function (Type $type, callable $traverse) use ($classReflection): Type {
				if ($type instanceof StaticType) {
					return $type->changeBaseClass($classReflection);
				}

				return $traverse($type);
			},
		);

		$parentReturnTag = $parentReturnTag->withType($parentType);

		// Each parent would overwrite the previous one except if it returns a less specific type.
		// Do not care for incompatible types as there is a separate rule for that.
		if ($returnTag !== null && $parentType->isSuperTypeOf($returnTag->getType())->yes()) {
			return null;
		}

		return self::resolveTemplateTypeInTag(
			$parentReturnTag->withType(
				$parameterMapping->transformConditionalReturnTypeWithParameterNameMapping($parentReturnTag->getType()),
			)->toImplicit(),
			$parentClass,
			TemplateTypeVariance::createCovariant(),
		);
	}

	/**
	 * @param array<AssertTag> $assertTags
	 * @return array<AssertTag>
	 */
	private static function mergeAssertTags(array $assertTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		if (count($assertTags) > 0) {
			return $assertTags;
		}

		return array_map(
			static fn (AssertTag $assertTag) => self::resolveTemplateTypeInTag(
				$assertTag->withParameter(
					$parameterMapping->transformAssertTagParameterWithParameterNameMapping($assertTag->getParameter()),
				)->toImplicit(),
				$parentClass,
				TemplateTypeVariance::createCovariant(),
			),
			$parent->getAssertTags(),
		);
	}

	private static function mergeSelfOutTypeTags(?SelfOutTypeTag $selfOutTypeTag, self $parent): ?SelfOutTypeTag
	{
		if ($selfOutTypeTag !== null) {
			return $selfOutTypeTag;
		}

		return $parent->getSelfOutTag();
	}

	private static function mergeDeprecatedTags(?DeprecatedTag $deprecatedTag, bool $hasNotDeprecatedTag, self $parent): ?DeprecatedTag
	{
		if ($deprecatedTag !== null) {
			return $deprecatedTag;
		}

		if ($hasNotDeprecatedTag) {
			return null;
		}

		$result = $parent->getDeprecatedTag();
		if ($result === null && !$parent->isNotDeprecated()) {
			return null;
		}

		return $result;
	}

	private static function mergeThrowsTags(?ThrowsTag $throwsTag, self $parent): ?ThrowsTag
	{
		if ($throwsTag !== null) {
			return $throwsTag;
		}

		return $parent->getThrowsTag();
	}

	/**
	 * @param array<string, ParamOutTag> $paramOutTags
	 * @return array<string, ParamOutTag>
	 */
	private static function mergeParamOutTags(array $paramOutTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		return self::mergeOneParentParamOutTags($paramOutTags, $parent, $parameterMapping, $parentClass);
	}

	/**
	 * @param array<string, ParamOutTag> $paramOutTags
	 * @return array<string, ParamOutTag>
	 */
	private static function mergeOneParentParamOutTags(array $paramOutTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		$parentParamOutTags = $parameterMapping->transformArrayKeysWithParameterNameMapping($parent->getParamOutTags());

		foreach ($parentParamOutTags as $name => $parentParamTag) {
			if (array_key_exists($name, $paramOutTags)) {
				continue;
			}

			$paramOutTags[$name] = self::resolveTemplateTypeInTag(
				$parentParamTag->withType($parameterMapping->transformConditionalReturnTypeWithParameterNameMapping($parentParamTag->getType())),
				$parentClass,
				TemplateTypeVariance::createCovariant(),
			);
		}

		return $paramOutTags;
	}

	/**
	 * @param array<string, bool> $paramsImmediatelyInvokedCallable
	 * @return array<string, bool>
	 */
	private static function mergeParamsImmediatelyInvokedCallable(array $paramsImmediatelyInvokedCallable, self $parent, InheritedPhpDocParameterMapping $parameterMapping): array
	{
		return self::mergeOneParentParamImmediatelyInvokedCallable($paramsImmediatelyInvokedCallable, $parent, $parameterMapping);
	}

	/**
	 * @param array<string, bool> $paramsImmediatelyInvokedCallable
	 * @return array<string, bool>
	 */
	private static function mergeOneParentParamImmediatelyInvokedCallable(array $paramsImmediatelyInvokedCallable, self $parent, InheritedPhpDocParameterMapping $parameterMapping): array
	{
		$parentImmediatelyInvokedCallable = $parameterMapping->transformArrayKeysWithParameterNameMapping($parent->getParamsImmediatelyInvokedCallable());

		foreach ($parentImmediatelyInvokedCallable as $name => $parentIsImmediatelyInvokedCallable) {
			if (array_key_exists($name, $paramsImmediatelyInvokedCallable)) {
				continue;
			}

			$paramsImmediatelyInvokedCallable[$name] = $parentIsImmediatelyInvokedCallable;
		}

		return $paramsImmediatelyInvokedCallable;
	}

	/**
	 * @param array<string, bool> $paramsPureUnlessCallableIsImpure
	 * @return array<string, bool>
	 */
	private static function mergeParamsPureUnlessCallableIsImpure(array $paramsPureUnlessCallableIsImpure, self $parent, InheritedPhpDocParameterMapping $parameterMapping): array
	{
		return self::mergeOneParentParamPureUnlessCallableIsImpure($paramsPureUnlessCallableIsImpure, $parent, $parameterMapping);
	}

	/**
	 * @param array<string, bool> $paramsPureUnlessCallableIsImpure
	 * @return array<string, bool>
	 */
	private static function mergeOneParentParamPureUnlessCallableIsImpure(array $paramsPureUnlessCallableIsImpure, self $parent, InheritedPhpDocParameterMapping $parameterMapping): array
	{
		$parentPureUnlessCallableIsImpure = $parameterMapping->transformArrayKeysWithParameterNameMapping($parent->getParamsPureUnlessCallableIsImpure());

		foreach ($parentPureUnlessCallableIsImpure as $name => $parentIsPureUnlessCallableIsImpure) {
			if (array_key_exists($name, $paramsPureUnlessCallableIsImpure)) {
				continue;
			}

			$paramsPureUnlessCallableIsImpure[$name] = $parentIsPureUnlessCallableIsImpure;
		}

		return $paramsPureUnlessCallableIsImpure;
	}

	/**
	 * @param array<string, bool> $paramsPureUnlessParameterPassed
	 * @return array<string, bool>
	 */
	private static function mergeParamsPureUnlessParameterPassed(array $paramsPureUnlessParameterPassed, self $parent, InheritedPhpDocParameterMapping $parameterMapping): array
	{
		return self::mergeOneParentParamPureUnlessParameterPassed($paramsPureUnlessParameterPassed, $parent, $parameterMapping);
	}

	/**
	 * @param array<string, bool> $paramsPureUnlessParameterPassed
	 * @return array<string, bool>
	 */
	private static function mergeOneParentParamPureUnlessParameterPassed(array $paramsPureUnlessParameterPassed, self $parent, InheritedPhpDocParameterMapping $parameterMapping): array
	{
		$parentPureUnlessParameterPassed = $parameterMapping->transformArrayKeysWithParameterNameMapping($parent->getParamsPureUnlessParameterPassed());

		foreach ($parentPureUnlessParameterPassed as $name => $parentIsPureUnlessParameterPassed) {
			if (array_key_exists($name, $paramsPureUnlessParameterPassed)) {
				continue;
			}

			$paramsPureUnlessParameterPassed[$name] = $parentIsPureUnlessParameterPassed;
		}

		return $paramsPureUnlessParameterPassed;
	}

	/**
	 * @param array<string, ParamClosureThisTag> $paramsClosureThisTags
	 * @return array<string, ParamClosureThisTag>
	 */
	private static function mergeParamClosureThisTags(array $paramsClosureThisTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		return self::mergeOneParentParamClosureThisTag($paramsClosureThisTags, $parent, $parameterMapping, $parentClass);
	}

	/**
	 * @param array<string, ParamClosureThisTag> $paramsClosureThisTags
	 * @return array<string, ParamClosureThisTag>
	 */
	private static function mergeOneParentParamClosureThisTag(array $paramsClosureThisTags, self $parent, InheritedPhpDocParameterMapping $parameterMapping, ClassReflection $parentClass): array
	{
		$parentClosureThisTags = $parameterMapping->transformArrayKeysWithParameterNameMapping($parent->getParamClosureThisTags());

		foreach ($parentClosureThisTags as $name => $parentParamClosureThisTag) {
			if (array_key_exists($name, $paramsClosureThisTags)) {
				continue;
			}

			$paramsClosureThisTags[$name] = self::resolveTemplateTypeInTag(
				$parentParamClosureThisTag->withType(
					$parameterMapping->transformConditionalReturnTypeWithParameterNameMapping($parentParamClosureThisTag->getType()),
				),
				$parentClass,
				TemplateTypeVariance::createContravariant(),
			);
		}

		return $paramsClosureThisTags;
	}

	private static function mergePureTags(?bool $isPure, self $parent): ?bool
	{
		if ($isPure !== null) {
			return $isPure;
		}

		return $parent->isPure();
	}

	/**
	 * @template T of TypedTag
	 * @param T $tag
	 * @return T
	 */
	private static function resolveTemplateTypeInTag(
		TypedTag $tag,
		ClassReflection $classReflection,
		TemplateTypeVariance $positionVariance,
	): TypedTag
	{
		$type = TemplateTypeHelper::resolveTemplateTypes(
			$tag->getType(),
			$classReflection->getActiveTemplateTypeMap(),
			$classReflection->getCallSiteVarianceMap(),
			$positionVariance,
		);
		return $tag->withType($type);
	}

}
