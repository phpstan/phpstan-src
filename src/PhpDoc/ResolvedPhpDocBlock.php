<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\PhpDoc\Tag\DeprecatedTag;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\ParamOutTag;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\PhpDoc\Tag\RequireImplementsTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
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

/** @api */
class ResolvedPhpDocBlock
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

	private ReturnTag|false|null $returnTag = false;

	private ThrowsTag|false|null $throwsTag = false;

	/** @var array<MixinTag>|false */
	private array|false $mixinTags = false;

	/** @var array<RequireExtendsTag>|false */
	private array|false $requireExtendsTags = false;

	/** @var array<RequireImplementsTag>|false */
	private array|false $requireImplementsTags = false;

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
		// new property also needs to be added to createEmpty() and merge()
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
		$self->returnTag = null;
		$self->throwsTag = null;
		$self->mixinTags = [];
		$self->requireExtendsTags = [];
		$self->requireImplementsTags = [];
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
		$self->isReadOnly = false;
		$self->isImmutable = false;
		$self->isAllowedPrivateMutation = false;
		$self->hasConsistentConstructor = false;
		$self->acceptsNamedArguments = true;

		return $self;
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 */
	public function merge(array $parents, array $parentPhpDocBlocks): self
	{
		$className = $this->nameScope !== null ? $this->nameScope->getClassName() : null;
		$classReflection = $className !== null && $this->reflectionProvider->hasClass($className)
			? $this->reflectionProvider->getClass($className)
			: null;

		// new property also needs to be added to createEmpty()
		$result = new self();
		// we will resolve everything on $this here so these properties don't have to be populated
		// skip $result->phpDocNode
		$phpDocNodes = $this->phpDocNodes;
		$acceptsNamedArguments = $this->acceptsNamedArguments();
		foreach ($parents as $parent) {
			foreach ($parent->phpDocNodes as $phpDocNode) {
				$phpDocNodes[] = $phpDocNode;
				$acceptsNamedArguments = $acceptsNamedArguments && $parent->acceptsNamedArguments();
			}
		}
		$result->phpDocNodes = $phpDocNodes;
		$result->phpDocString = $this->phpDocString;
		$result->filename = $this->filename;
		// skip $result->nameScope
		$result->templateTypeMap = $this->templateTypeMap;
		$result->templateTags = $this->templateTags;
		// skip $result->phpDocNodeResolver
		$result->varTags = self::mergeVarTags($this->getVarTags(), $parents, $parentPhpDocBlocks);
		$result->methodTags = $this->getMethodTags();
		$result->propertyTags = $this->getPropertyTags();
		$result->extendsTags = $this->getExtendsTags();
		$result->implementsTags = $this->getImplementsTags();
		$result->usesTags = $this->getUsesTags();
		$result->paramTags = self::mergeParamTags($this->getParamTags(), $parents, $parentPhpDocBlocks);
		$result->paramOutTags = self::mergeParamOutTags($this->getParamOutTags(), $parents, $parentPhpDocBlocks);
		$result->returnTag = self::mergeReturnTags($this->getReturnTag(), $classReflection, $parents, $parentPhpDocBlocks);
		$result->throwsTag = self::mergeThrowsTags($this->getThrowsTag(), $parents);
		$result->mixinTags = $this->getMixinTags();
		$result->requireExtendsTags = $this->getRequireExtendsTags();
		$result->requireImplementsTags = $this->getRequireImplementsTags();
		$result->typeAliasTags = $this->getTypeAliasTags();
		$result->typeAliasImportTags = $this->getTypeAliasImportTags();
		$result->assertTags = self::mergeAssertTags($this->getAssertTags(), $parents, $parentPhpDocBlocks);
		$result->selfOutTypeTag = self::mergeSelfOutTypeTags($this->getSelfOutTag(), $parents);
		$result->deprecatedTag = self::mergeDeprecatedTags($this->getDeprecatedTag(), $this->isNotDeprecated(), $parents);
		$result->isDeprecated = $result->deprecatedTag !== null;
		$result->isNotDeprecated = $this->isNotDeprecated();
		$result->isInternal = $this->isInternal();
		$result->isFinal = $this->isFinal();
		$result->isPure = self::mergePureTags($this->isPure(), $parents);
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

		$paramTags = $this->getParamTags();

		$newParamTags = [];
		foreach ($paramTags as $key => $paramTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}
			$transformedType = TypeTraverser::map($paramTag->getType(), $mapParameterCb);
			$paramTag = $paramTag->withType($transformedType);
			if ($paramTag->getClosureThisType() !== null) {
				$transformedClosureThisType = TypeTraverser::map($paramTag->getClosureThisType(), $mapParameterCb);
				$paramTag = $paramTag->withClosureThisType($transformedClosureThisType);
			}
			$newParamTags[$parameterNameMapping[$key]] = $paramTag;
		}

		$paramOutTags = $this->getParamOutTags();

		$newParamOutTags = [];
		foreach ($paramOutTags as $key => $paramOutTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}

			$transformedType = TypeTraverser::map($paramOutTag->getType(), $mapParameterCb);
			$newParamOutTags[$parameterNameMapping[$key]] = $paramOutTag->withType($transformedType);
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
		if ($this->isDeprecated === null) {
			$this->isDeprecated = $this->phpDocNodeResolver->resolveIsDeprecated(
				$this->phpDocNode,
			);
		}
		return $this->isDeprecated;
	}

	/**
	 * @internal
	 */
	public function isNotDeprecated(): bool
	{
		if ($this->isNotDeprecated === null) {
			$this->isNotDeprecated = $this->phpDocNodeResolver->resolveIsNotDeprecated(
				$this->phpDocNode,
			);
		}
		return $this->isNotDeprecated;
	}

	public function isInternal(): bool
	{
		if ($this->isInternal === null) {
			$this->isInternal = $this->phpDocNodeResolver->resolveIsInternal(
				$this->phpDocNode,
			);
		}
		return $this->isInternal;
	}

	public function isFinal(): bool
	{
		if ($this->isFinal === null) {
			$this->isFinal = $this->phpDocNodeResolver->resolveIsFinal(
				$this->phpDocNode,
			);
		}
		return $this->isFinal;
	}

	public function hasConsistentConstructor(): bool
	{
		if ($this->hasConsistentConstructor === null) {
			$this->hasConsistentConstructor = $this->phpDocNodeResolver->resolveHasConsistentConstructor(
				$this->phpDocNode,
			);
		}
		return $this->hasConsistentConstructor;
	}

	public function acceptsNamedArguments(): bool
	{
		if ($this->acceptsNamedArguments === null) {
			$this->acceptsNamedArguments = $this->phpDocNodeResolver->resolveAcceptsNamedArguments(
				$this->phpDocNode,
			);
		}
		return $this->acceptsNamedArguments;
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

	public function isReadOnly(): bool
	{
		if ($this->isReadOnly === null) {
			$this->isReadOnly = $this->phpDocNodeResolver->resolveIsReadOnly(
				$this->phpDocNode,
			);
		}
		return $this->isReadOnly;
	}

	public function isImmutable(): bool
	{
		if ($this->isImmutable === null) {
			$this->isImmutable = $this->phpDocNodeResolver->resolveIsImmutable(
				$this->phpDocNode,
			);
		}
		return $this->isImmutable;
	}

	public function isAllowedPrivateMutation(): bool
	{
		if ($this->isAllowedPrivateMutation === null) {
			$this->isAllowedPrivateMutation = $this->phpDocNodeResolver->resolveAllowPrivateMutation(
				$this->phpDocNode,
			);
		}

		return $this->isAllowedPrivateMutation;
	}

	/**
	 * @param array<string|int, VarTag> $varTags
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return array<string|int, VarTag>
	 */
	private static function mergeVarTags(array $varTags, array $parents, array $parentPhpDocBlocks): array
	{
		// Only allow one var tag per comment. Check the parent if child does not have this tag.
		if (count($varTags) > 0) {
			return $varTags;
		}

		foreach ($parents as $i => $parent) {
			$result = self::mergeOneParentVarTags($parent, $parentPhpDocBlocks[$i]);
			if ($result === null) {
				continue;
			}

			return $result;
		}

		return [];
	}

	/**
	 * @param ResolvedPhpDocBlock $parent
	 * @return array<string|int, VarTag>|null
	 */
	private static function mergeOneParentVarTags(self $parent, PhpDocBlock $phpDocBlock): ?array
	{
		foreach ($parent->getVarTags() as $key => $parentVarTag) {
			return [$key => self::resolveTemplateTypeInTag($parentVarTag, $phpDocBlock, TemplateTypeVariance::createInvariant())];
		}

		return null;
	}

	/**
	 * @param array<string, ParamTag> $paramTags
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return array<string, ParamTag>
	 */
	private static function mergeParamTags(array $paramTags, array $parents, array $parentPhpDocBlocks): array
	{
		foreach ($parents as $i => $parent) {
			$paramTags = self::mergeOneParentParamTags($paramTags, $parent, $parentPhpDocBlocks[$i]);
		}

		return $paramTags;
	}

	/**
	 * @param array<string, ParamTag> $paramTags
	 * @param ResolvedPhpDocBlock $parent
	 * @return array<string, ParamTag>
	 */
	private static function mergeOneParentParamTags(array $paramTags, self $parent, PhpDocBlock $phpDocBlock): array
	{
		$parentParamTags = $phpDocBlock->transformArrayKeysWithParameterNameMapping($parent->getParamTags());

		foreach ($parentParamTags as $name => $parentParamTag) {
			if (array_key_exists($name, $paramTags)) {
				if ($paramTags[$name]->isImmediatelyInvokedCallable()->maybe()) {
					$paramTags[$name] = $paramTags[$name]->withImmediatelyInvokedCallable($parentParamTag->isImmediatelyInvokedCallable());
				}
				if (
					$paramTags[$name]->getClosureThisType() === null
					&& $parentParamTag->getClosureThisType() !== null
				) {
					$paramTags[$name] = $paramTags[$name]->withClosureThisType($phpDocBlock->transformConditionalReturnTypeWithParameterNameMapping($parentParamTag->getClosureThisType()));
				}
				continue;
			}

			$parentParamTag = $parentParamTag->withType($phpDocBlock->transformConditionalReturnTypeWithParameterNameMapping($parentParamTag->getType()));
			if ($parentParamTag->getClosureThisType() !== null) {
				$parentParamTag = $parentParamTag->withClosureThisType($phpDocBlock->transformConditionalReturnTypeWithParameterNameMapping($parentParamTag->getClosureThisType()));
			}

			$paramTags[$name] = self::resolveTemplateTypeInTag(
				$parentParamTag,
				$phpDocBlock,
				TemplateTypeVariance::createContravariant(),
			);
		}

		return $paramTags;
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return ReturnTag|Null
	 */
	private static function mergeReturnTags(?ReturnTag $returnTag, ?ClassReflection $classReflection, array $parents, array $parentPhpDocBlocks): ?ReturnTag
	{
		if ($returnTag !== null) {
			return $returnTag;
		}

		foreach ($parents as $i => $parent) {
			$result = self::mergeOneParentReturnTag($returnTag, $classReflection, $parent, $parentPhpDocBlocks[$i]);
			if ($result === null) {
				continue;
			}

			return $result;
		}

		return null;
	}

	private static function mergeOneParentReturnTag(?ReturnTag $returnTag, ?ClassReflection $classReflection, self $parent, PhpDocBlock $phpDocBlock): ?ReturnTag
	{
		$parentReturnTag = $parent->getReturnTag();
		if ($parentReturnTag === null) {
			return $returnTag;
		}

		$parentType = $parentReturnTag->getType();

		if ($classReflection !== null) {
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
		}

		// Each parent would overwrite the previous one except if it returns a less specific type.
		// Do not care for incompatible types as there is a separate rule for that.
		if ($returnTag !== null && $parentType->isSuperTypeOf($returnTag->getType())->yes()) {
			return null;
		}

		return self::resolveTemplateTypeInTag(
			$parentReturnTag->withType(
				$phpDocBlock->transformConditionalReturnTypeWithParameterNameMapping($parentReturnTag->getType()),
			)->toImplicit(),
			$phpDocBlock,
			TemplateTypeVariance::createCovariant(),
		);
	}

	/**
	 * @param array<AssertTag> $assertTags
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return array<AssertTag>
	 */
	private static function mergeAssertTags(array $assertTags, array $parents, array $parentPhpDocBlocks): array
	{
		if (count($assertTags) > 0) {
			return $assertTags;
		}
		foreach ($parents as $i => $parent) {
			$result = $parent->getAssertTags();
			if (count($result) === 0) {
				continue;
			}

			$phpDocBlock = $parentPhpDocBlocks[$i];

			return array_map(
				static fn (AssertTag $assertTag) => self::resolveTemplateTypeInTag(
					$assertTag->withParameter(
						$phpDocBlock->transformAssertTagParameterWithParameterNameMapping($assertTag->getParameter()),
					)->toImplicit(),
					$phpDocBlock,
					TemplateTypeVariance::createCovariant(),
				),
				$result,
			);
		}

		return $assertTags;
	}

	/**
	 * @param array<int, self> $parents
	 */
	private static function mergeSelfOutTypeTags(?SelfOutTypeTag $selfOutTypeTag, array $parents): ?SelfOutTypeTag
	{
		if ($selfOutTypeTag !== null) {
			return $selfOutTypeTag;
		}
		foreach ($parents as $parent) {
			$result = $parent->getSelfOutTag();
			if ($result === null) {
				continue;
			}
			return $result;
		}

		return null;
	}

	/**
	 * @param array<int, self> $parents
	 */
	private static function mergeDeprecatedTags(?DeprecatedTag $deprecatedTag, bool $hasNotDeprecatedTag, array $parents): ?DeprecatedTag
	{
		if ($deprecatedTag !== null) {
			return $deprecatedTag;
		}

		if ($hasNotDeprecatedTag) {
			return null;
		}

		foreach ($parents as $parent) {
			$result = $parent->getDeprecatedTag();
			if ($result === null && !$parent->isNotDeprecated()) {
				continue;
			}
			return $result;
		}

		return null;
	}

	/**
	 * @param array<int, self> $parents
	 */
	private static function mergeThrowsTags(?ThrowsTag $throwsTag, array $parents): ?ThrowsTag
	{
		if ($throwsTag !== null) {
			return $throwsTag;
		}
		foreach ($parents as $parent) {
			$result = $parent->getThrowsTag();
			if ($result === null) {
				continue;
			}

			return $result;
		}

		return null;
	}

	/**
	 * @param array<string, ParamOutTag> $paramOutTags
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return array<string, ParamOutTag>
	 */
	private static function mergeParamOutTags(array $paramOutTags, array $parents, array $parentPhpDocBlocks): array
	{
		foreach ($parents as $i => $parent) {
			$paramOutTags = self::mergeOneParentParamOutTags($paramOutTags, $parent, $parentPhpDocBlocks[$i]);
		}

		return $paramOutTags;
	}

	/**
	 * @param array<string, ParamOutTag> $paramOutTags
	 * @param ResolvedPhpDocBlock $parent
	 * @return array<string, ParamOutTag>
	 */
	private static function mergeOneParentParamOutTags(array $paramOutTags, self $parent, PhpDocBlock $phpDocBlock): array
	{
		$parentParamOutTags = $phpDocBlock->transformArrayKeysWithParameterNameMapping($parent->getParamOutTags());

		foreach ($parentParamOutTags as $name => $parentParamTag) {
			if (array_key_exists($name, $paramOutTags)) {
				continue;
			}

			$paramOutTags[$name] = self::resolveTemplateTypeInTag($parentParamTag, $phpDocBlock, TemplateTypeVariance::createCovariant());
		}

		return $paramOutTags;
	}

	/**
	 * @param array<int, self> $parents
	 */
	private static function mergePureTags(?bool $isPure, array $parents): ?bool
	{
		if ($isPure !== null) {
			return $isPure;
		}

		foreach ($parents as $parent) {
			$parentIsPure = $parent->isPure();
			if ($parentIsPure === null) {
				continue;
			}

			return $parentIsPure;
		}

		return null;
	}

	/**
	 * @template T of TypedTag
	 * @param T $tag
	 * @return T
	 */
	private static function resolveTemplateTypeInTag(
		TypedTag $tag,
		PhpDocBlock $phpDocBlock,
		TemplateTypeVariance $positionVariance,
	): TypedTag
	{
		$type = TemplateTypeHelper::resolveTemplateTypes(
			$tag->getType(),
			$phpDocBlock->getClassReflection()->getActiveTemplateTypeMap(),
			$phpDocBlock->getClassReflection()->getCallSiteVarianceMap(),
			$positionVariance,
		);
		return $tag->withType($type);
	}

}
