<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\DeprecatedTag;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\Tag\ThrowsTag;
use PHPStan\PhpDoc\Tag\TypeAliasImportTag;
use PHPStan\PhpDoc\Tag\TypeAliasTag;
use PHPStan\PhpDoc\Tag\TypedTag;
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function count;
use function is_bool;
use function substr;

/** @api */
class ResolvedPhpDocBlock
{

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

	private ReturnTag|false|null $returnTag = false;

	private ThrowsTag|false|null $throwsTag = false;

	/** @var array<MixinTag>|false */
	private array|false $mixinTags = false;

	/** @var array<TypeAliasTag>|false */
	private array|false $typeAliasTags = false;

	/** @var array<TypeAliasImportTag>|false */
	private array|false $typeAliasImportTags = false;

	private DeprecatedTag|false|null $deprecatedTag = false;

	private ?bool $isDeprecated = null;

	private ?bool $isInternal = null;

	private ?bool $isFinal = null;

	/** @var bool|'notLoaded'|null */
	private bool|string|null $isPure = 'notLoaded';

	private ?bool $isReadonly = null;

	private ?bool $hasConsistentConstructor = null;

	private function __construct()
	{
	}

	/**
	 * @param TemplateTag[] $templateTags
	 */
	public static function create(
		PhpDocNode $phpDocNode,
		string $phpDocString,
		string $filename,
		NameScope $nameScope,
		TemplateTypeMap $templateTypeMap,
		array $templateTags,
		PhpDocNodeResolver $phpDocNodeResolver,
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

		return $self;
	}

	public static function createEmpty(): self
	{
		// new property also needs to be added to merge()
		$self = new self();
		$self->phpDocString = '/** */';
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
		$self->returnTag = null;
		$self->throwsTag = null;
		$self->mixinTags = [];
		$self->typeAliasTags = [];
		$self->typeAliasImportTags = [];
		$self->deprecatedTag = null;
		$self->isDeprecated = false;
		$self->isInternal = false;
		$self->isFinal = false;
		$self->isPure = null;
		$self->isReadonly = false;
		$self->hasConsistentConstructor = false;

		return $self;
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 */
	public function merge(array $parents, array $parentPhpDocBlocks): self
	{
		// new property also needs to be added to createEmpty()
		$result = new self();
		// we will resolve everything on $this here so these properties don't have to be populated
		// skip $result->phpDocNode
		// skip $result->phpDocString - just for stubs
		$phpDocNodes = $this->phpDocNodes;
		foreach ($parents as $parent) {
			foreach ($parent->phpDocNodes as $phpDocNode) {
				$phpDocNodes[] = $phpDocNode;
			}
		}
		$result->phpDocNodes = $phpDocNodes;
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
		$result->returnTag = self::mergeReturnTags($this->getReturnTag(), $parents, $parentPhpDocBlocks);
		$result->throwsTag = self::mergeThrowsTags($this->getThrowsTag(), $parents);
		$result->mixinTags = $this->getMixinTags();
		$result->typeAliasTags = $this->getTypeAliasTags();
		$result->typeAliasImportTags = $this->getTypeAliasImportTags();
		$result->deprecatedTag = self::mergeDeprecatedTags($this->getDeprecatedTag(), $parents);
		$result->isDeprecated = $result->deprecatedTag !== null;
		$result->isInternal = $this->isInternal();
		$result->isFinal = $this->isFinal();
		$result->isPure = $this->isPure();
		$result->isReadonly = $this->isReadonly();
		$result->hasConsistentConstructor = $this->hasConsistentConstructor();

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

		$paramTags = $this->getParamTags();

		$newParamTags = [];
		foreach ($paramTags as $key => $paramTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}
			$newParamTags[$parameterNameMapping[$key]] = $paramTag;
		}

		$returnTag = $this->getReturnTag();
		if ($returnTag !== null) {
			$transformedType = TypeTraverser::map($returnTag->getType(), static function (Type $type, callable $traverse) use ($parameterNameMapping): Type {
				if ($type instanceof ConditionalTypeForParameter) {
					$parameterName = substr($type->getParameterName(), 1);
					if (array_key_exists($parameterName, $parameterNameMapping)) {
						$type = $type->changeParameterName('$' . $parameterNameMapping[$parameterName]);
					}
				}

				return $traverse($type);
			});
			$returnTag = $returnTag->withType($transformedType);
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
		$self->varTags = $this->varTags;
		$self->methodTags = $this->methodTags;
		$self->propertyTags = $this->propertyTags;
		$self->extendsTags = $this->extendsTags;
		$self->implementsTags = $this->implementsTags;
		$self->usesTags = $this->usesTags;
		$self->paramTags = $newParamTags;
		$self->returnTag = $returnTag;
		$self->throwsTag = $this->throwsTag;
		$self->mixinTags = $this->mixinTags;
		$self->typeAliasTags = $this->typeAliasTags;
		$self->typeAliasImportTags = $this->typeAliasImportTags;
		$self->deprecatedTag = $this->deprecatedTag;
		$self->isDeprecated = $this->isDeprecated;
		$self->isInternal = $this->isInternal;
		$self->isFinal = $this->isFinal;
		$self->isPure = $this->isPure;

		return $self;
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
			} else {
				$impure = $this->phpDocNodeResolver->resolveIsImpure(
					$this->phpDocNode,
				);
				if ($impure) {
					$this->isPure = false;
					return $this->isPure;
				}
			}

			$this->isPure = null;
		}

		return $this->isPure;
	}

	public function isReadonly(): bool
	{
		if ($this->isReadonly === null) {
			$this->isReadonly = $this->phpDocNodeResolver->resolveIsReadonly(
				$this->phpDocNode,
			);
		}
		return $this->isReadonly;
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
			return [$key => self::resolveTemplateTypeInTag($parentVarTag, $phpDocBlock)];
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
				continue;
			}

			$paramTags[$name] = self::resolveTemplateTypeInTag($parentParamTag, $phpDocBlock);
		}

		return $paramTags;
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return ReturnTag|Null
	 */
	private static function mergeReturnTags(?ReturnTag $returnTag, array $parents, array $parentPhpDocBlocks): ?ReturnTag
	{
		if ($returnTag !== null) {
			return $returnTag;
		}

		foreach ($parents as $i => $parent) {
			$result = self::mergeOneParentReturnTag($returnTag, $parent, $parentPhpDocBlocks[$i]);
			if ($result === null) {
				continue;
			}

			return $result;
		}

		return null;
	}

	private static function mergeOneParentReturnTag(?ReturnTag $returnTag, self $parent, PhpDocBlock $phpDocBlock): ?ReturnTag
	{
		$parentReturnTag = $parent->getReturnTag();
		if ($parentReturnTag === null) {
			return $returnTag;
		}

		$parentType = $parentReturnTag->getType();

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
		);
	}

	/**
	 * @param array<int, self> $parents
	 */
	private static function mergeDeprecatedTags(?DeprecatedTag $deprecatedTag, array $parents): ?DeprecatedTag
	{
		if ($deprecatedTag !== null) {
			return $deprecatedTag;
		}
		foreach ($parents as $parent) {
			$result = $parent->getDeprecatedTag();
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
	 * @template T of TypedTag
	 * @param T $tag
	 * @return T
	 */
	private static function resolveTemplateTypeInTag(TypedTag $tag, PhpDocBlock $phpDocBlock): TypedTag
	{
		$type = TemplateTypeHelper::resolveTemplateTypes(
			$tag->getType(),
			$phpDocBlock->getClassReflection()->getActiveTemplateTypeMap(),
		);
		return $tag->withType($type);
	}

}
