<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\ThrowsTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\TypeCombinator;

class ResolvedPhpDocBlock
{

	/** @var PhpDocNode */
	private $phpDocNode;

	/** @var string */
	private $phpDocString;

	/** @var string|null */
	private $filename;

	/** @var NameScope */
	private $nameScope;

	/** @var TemplateTypeMap */
	private $templateTypeMap;

	/** @var array<string, \PHPStan\PhpDoc\Tag\TemplateTag> */
	private $templateTags;

	/** @var \PHPStan\PhpDoc\PhpDocNodeResolver */
	private $phpDocNodeResolver;

	/** @var array<string|int, \PHPStan\PhpDoc\Tag\VarTag>|false */
	private $varTags = false;

	/** @var array<string, \PHPStan\PhpDoc\Tag\MethodTag>|false */
	private $methodTags = false;

	/** @var array<string, \PHPStan\PhpDoc\Tag\PropertyTag>|false */
	private $propertyTags = false;

	/** @var array<string, \PHPStan\PhpDoc\Tag\ExtendsTag>|false */
	private $extendsTags = false;

	/** @var array<string, \PHPStan\PhpDoc\Tag\ImplementsTag>|false */
	private $implementsTags = false;

	/** @var array<string, \PHPStan\PhpDoc\Tag\UsesTag>|false */
	private $usesTags = false;

	/** @var array<string, \PHPStan\PhpDoc\Tag\ParamTag>|false */
	private $paramTags = false;

	/** @var \PHPStan\PhpDoc\Tag\ReturnTag|false|null */
	private $returnTag = false;

	/** @var \PHPStan\PhpDoc\Tag\ThrowsTag|false|null */
	private $throwsTag = false;

	/** @var \PHPStan\PhpDoc\Tag\DeprecatedTag|false|null */
	private $deprecatedTag = false;

	/** @var bool|null */
	private $isDeprecated;

	/** @var bool|null */
	private $isInternal;

	/** @var bool|null */
	private $isFinal;

	private function __construct()
	{
	}

	/**
	 * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode $phpDocNode
	 * @param string $phpDocString
	 * @param string $filename
	 * @param \PHPStan\Analyser\NameScope $nameScope
	 * @param \PHPStan\Type\Generic\TemplateTypeMap $templateTypeMap
	 * @param \PHPStan\PhpDoc\Tag\TemplateTag[] $templateTags
	 * @param \PHPStan\PhpDoc\PhpDocNodeResolver $phpDocNodeResolver
	 * @return self
	 */
	public static function create(
		PhpDocNode $phpDocNode,
		string $phpDocString,
		string $filename,
		NameScope $nameScope,
		TemplateTypeMap $templateTypeMap,
		array $templateTags,
		PhpDocNodeResolver $phpDocNodeResolver
	): self
	{
		$self = new self();
		$self->phpDocNode = $phpDocNode;
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
		$self = new self();
		$self->phpDocNode = new PhpDocNode([]);
		$self->phpDocString = '/** */';
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
		$self->deprecatedTag = null;
		$self->isDeprecated = false;
		$self->isInternal = false;
		$self->isFinal = false;

		return $self;
	}

	/**
	 * @param array<string, string> $parameterNameMapping
	 * @return self
	 */
	public function changeParameterNamesByMapping(array $parameterNameMapping): self
	{
		$paramTags = $this->getParamTags();

		$newParamTags = [];
		foreach ($paramTags as $key => $paramTag) {
			if (!array_key_exists($key, $parameterNameMapping)) {
				continue;
			}
			$newParamTags[$parameterNameMapping[$key]] = $paramTag;
		}

		$self = new self();
		$self->phpDocNode = $this->phpDocNode;
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
		$self->returnTag = $this->returnTag;
		$self->throwsTag = $this->throwsTag;
		$self->deprecatedTag = $this->deprecatedTag;
		$self->isDeprecated = $this->isDeprecated;
		$self->isInternal = $this->isInternal;
		$self->isFinal = $this->isFinal;

		return $self;
	}

	public function getPhpDocNode(): PhpDocNode
	{
		return $this->phpDocNode;
	}

	public function getPhpDocString(): string
	{
		return $this->phpDocString;
	}

	public function getFilename(): ?string
	{
		return $this->filename;
	}

	/**
	 * @return array<string|int, \PHPStan\PhpDoc\Tag\VarTag>
	 */
	public function getVarTags(): array
	{
		if ($this->varTags === false) {
			$this->varTags = $this->phpDocNodeResolver->resolveVarTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->varTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\MethodTag>
	 */
	public function getMethodTags(): array
	{
		if ($this->methodTags === false) {
			$this->methodTags = $this->phpDocNodeResolver->resolveMethodTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->methodTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\PropertyTag>
	 */
	public function getPropertyTags(): array
	{
		if ($this->propertyTags === false) {
			$this->propertyTags = $this->phpDocNodeResolver->resolvePropertyTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->propertyTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\TemplateTag>
	 */
	public function getTemplateTags(): array
	{
		return $this->templateTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\ExtendsTag>
	 */
	public function getExtendsTags(): array
	{
		if ($this->extendsTags === false) {
			$this->extendsTags = $this->phpDocNodeResolver->resolveExtendsTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->extendsTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\ImplementsTag>
	 */
	public function getImplementsTags(): array
	{
		if ($this->implementsTags === false) {
			$this->implementsTags = $this->phpDocNodeResolver->resolveImplementsTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->implementsTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\UsesTag>
	 */
	public function getUsesTags(): array
	{
		if ($this->usesTags === false) {
			$this->usesTags = $this->phpDocNodeResolver->resolveUsesTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->usesTags;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\ParamTag>
	 */
	public function getParamTags(): array
	{
		if ($this->paramTags === false) {
			$this->paramTags = $this->phpDocNodeResolver->resolveParamTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->paramTags;
	}

	public function getReturnTag(): ?\PHPStan\PhpDoc\Tag\ReturnTag
	{
		if ($this->returnTag === false) {
			$this->returnTag = $this->phpDocNodeResolver->resolveReturnTag(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->returnTag;
	}

	public function getThrowsTag(): ?\PHPStan\PhpDoc\Tag\ThrowsTag
	{
		if ($this->throwsTag === false) {
			$this->throwsTag = $this->phpDocNodeResolver->resolveThrowsTags(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->throwsTag;
	}

	public function getDeprecatedTag(): ?\PHPStan\PhpDoc\Tag\DeprecatedTag
	{
		if ($this->deprecatedTag === false) {
			$this->deprecatedTag = $this->phpDocNodeResolver->resolveDeprecatedTag(
				$this->phpDocNode,
				$this->nameScope
			);
		}
		return $this->deprecatedTag;
	}

	public function isDeprecated(): bool
	{
		if ($this->isDeprecated === null) {
			$this->isDeprecated = $this->phpDocNodeResolver->resolveIsDeprecated(
				$this->phpDocNode
			);
		}
		return $this->isDeprecated;
	}

	public function isInternal(): bool
	{
		if ($this->isInternal === null) {
			$this->isInternal = $this->phpDocNodeResolver->resolveIsInternal(
				$this->phpDocNode
			);
		}
		return $this->isInternal;
	}

	public function isFinal(): bool
	{
		if ($this->isFinal === null) {
			$this->isFinal = $this->phpDocNodeResolver->resolveIsFinal(
				$this->phpDocNode
			);
		}
		return $this->isFinal;
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 * @return self
	 */
	public function cloneAndMerge(array $parents, array $parentPhpDocBlocks): self
	{
		$result = clone $this;

		$result->mergeVarTags($parents);
		$result->mergeParamTags($parents, $parentPhpDocBlocks);
		$result->mergeReturnTags($parents, $parentPhpDocBlocks);
		$result->mergeThrowsTags($parents);
		$result->mergeDeprecatedTags($parents);

		return $result;
	}

	/**
	 * @param array<int, self> $parents
	 */
	private function mergeVarTags(array $parents): void
	{
		// Only allow one var tag per comment. Check the parent if child does not have this tag.
		if (count($this->getVarTags()) > 0) {
			return;
		}

		foreach ($parents as $parent) {
			$this->mergeOneParentVarTags($parent);
		}
	}

	private function mergeOneParentVarTags(self $parent): void
	{
		$parentVarTags = $parent->getVarTags();
		if (count($parentVarTags) > 0) {
			$this->varTags[0] = $parentVarTags[0];
		}
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 */
	public function mergeParamTags(array $parents, array $parentPhpDocBlocks): void
	{
		$this->getParamTags();

		foreach ($parents as $i => $parent) {
			$this->mergeOneParentParamTags($parent, $parentPhpDocBlocks[$i]);
		}
	}

	private function mergeOneParentParamTags(self $parent, PhpDocBlock $phpDocBlock): void
	{
		$parentParamTags = $phpDocBlock->transformArrayKeysWithParameterNameMapping($parent->getParamTags());

		foreach ($parentParamTags as $name => $parentParamTag) {
			if (!array_key_exists($name, $this->paramTags ?: [])) {
				$this->paramTags[$name] = $this->resolveTemplateTypeInTag($parentParamTag, $phpDocBlock);
			}
		}
	}

	/**
	 * @param array<int, self> $parents
	 * @param array<int, PhpDocBlock> $parentPhpDocBlocks
	 */
	private function mergeReturnTags(array $parents, array $parentPhpDocBlocks): void
	{
		if ($this->getReturnTag() !== null) {
			return;
		}

		foreach ($parents as $i => $parent) {
			$this->mergeOneParentReturnTag($parent, $parentPhpDocBlocks[$i]);
		}
	}

	private function mergeOneParentReturnTag(self $parent, PhpDocBlock $phpDocBlock): void
	{
		$parentReturnTag = $parent->getReturnTag();
		if ($parentReturnTag) {
			$parentType = $parentReturnTag->getType();

			// Each parent would overwrite the previous one except if it returns a less specific type.
			// Do not care for incompatible types as there is a separate rule for that.
			if ($this->returnTag === null || !$parentType->isSuperTypeOf($this->returnTag->getType())->yes()) {
				$this->returnTag = $this->resolveTemplateTypeInTag($parentReturnTag->cloneImplicit(), $phpDocBlock);
			}
		}
	}

	/**
	 * @param array<int, self> $parents
	 */
	private function mergeThrowsTags(array $parents): void
	{
		$this->getThrowsTag();

		foreach ($parents as $parent) {
			$this->mergeOneParentThrowsTag($parent);
		}
	}

	private function mergeOneParentThrowsTag(self $parent): void
	{
		$parentThrowsTag = $parent->getThrowsTag();

		if ($parentThrowsTag !== null) {
			if (!$this->throwsTag) {
				$this->throwsTag = $parentThrowsTag;
			} else {
				$type = TypeCombinator::union($this->throwsTag->getType(), $parentThrowsTag->getType());
				$this->throwsTag = new ThrowsTag($type);
			}
		}
	}

	/**
	 * @param array<int, self> $parents
	 */
	private function mergeDeprecatedTags(array $parents): void
	{
		$this->getDeprecatedTag();

		foreach ($parents as $parent) {
			$this->mergeOneParentDeprecatedTag($parent);
		}

		$this->isDeprecated = ($this->deprecatedTag !== null);
	}

	private function mergeOneParentDeprecatedTag(self $parent): void
	{
		$parentDeprecatedTag = $parent->getDeprecatedTag();
		if ($parentDeprecatedTag) {
			$this->deprecatedTag = $parentDeprecatedTag;
		}
	}

	/**
	 * @template T of TypedTag
	 * @param T $tag
	 * @param PhpDocBlock $phpDocBlock
	 * @return T
	 */
	private function resolveTemplateTypeInTag($tag, PhpDocBlock $phpDocBlock)
	{
		$type = $phpDocBlock->resolveTemplateTypeIfAny($tag->getType());
		return $tag->withType($type);
	}

}
