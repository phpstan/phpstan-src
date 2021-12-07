<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\DeprecatedTag;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MethodTagParameter;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\Tag\ThrowsTag;
use PHPStan\PhpDoc\Tag\TypeAliasImportTag;
use PHPStan\PhpDoc\Tag\TypeAliasTag;
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MixinTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_reverse;
use function count;
use function in_array;
use function strpos;
use function substr;

class PhpDocNodeResolver
{

	private TypeNodeResolver $typeNodeResolver;

	private ConstExprNodeResolver $constExprNodeResolver;

	private UnresolvableTypeHelper $unresolvableTypeHelper;

	public function __construct(
		TypeNodeResolver $typeNodeResolver,
		ConstExprNodeResolver $constExprNodeResolver,
		UnresolvableTypeHelper $unresolvableTypeHelper
	)
	{
		$this->typeNodeResolver = $typeNodeResolver;
		$this->constExprNodeResolver = $constExprNodeResolver;
		$this->unresolvableTypeHelper = $unresolvableTypeHelper;
	}

	/**
	 * @return array<(string|int), VarTag>
	 */
	public function resolveVarTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		$resolvedByTag = [];
		foreach (['@var', '@psalm-var', '@phpstan-var'] as $tagName) {
			$tagResolved = [];
			foreach ($phpDocNode->getVarTagValues($tagName) as $tagValue) {
				$type = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
				if ($this->shouldSkipType($tagName, $type)) {
					continue;
				}
				if ($tagValue->variableName !== '') {
					$variableName = substr($tagValue->variableName, 1);
					$resolved[$variableName] = new VarTag($type);
				} else {
					$varTag = new VarTag($type);
					$tagResolved[] = $varTag;
				}
			}

			if (count($tagResolved) === 0) {
				continue;
			}

			$resolvedByTag[] = $tagResolved;
		}

		if (count($resolvedByTag) > 0) {
			return array_reverse($resolvedByTag)[0];
		}

		return $resolved;
	}

	/**
	 * @return array<string, PropertyTag>
	 */
	public function resolvePropertyTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@property', '@phpstan-property'] as $tagName) {
			foreach ($phpDocNode->getPropertyTagValues($tagName) as $tagValue) {
				$propertyName = substr($tagValue->propertyName, 1);
				$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

				$resolved[$propertyName] = new PropertyTag(
					$propertyType,
					true,
					true
				);
			}
		}

		foreach (['@property-read', '@phpstan-property-read'] as $tagName) {
			foreach ($phpDocNode->getPropertyReadTagValues($tagName) as $tagValue) {
				$propertyName = substr($tagValue->propertyName, 1);
				$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

				$resolved[$propertyName] = new PropertyTag(
					$propertyType,
					true,
					false
				);
			}
		}

		foreach (['@property-write', '@phpstan-property-write'] as $tagName) {
			foreach ($phpDocNode->getPropertyWriteTagValues($tagName) as $tagValue) {
				$propertyName = substr($tagValue->propertyName, 1);
				$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

				$resolved[$propertyName] = new PropertyTag(
					$propertyType,
					false,
					true
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, MethodTag>
	 */
	public function resolveMethodTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@method', '@psalm-method', '@phpstan-method'] as $tagName) {
			foreach ($phpDocNode->getMethodTagValues($tagName) as $tagValue) {
				$parameters = [];
				foreach ($tagValue->parameters as $parameterNode) {
					$parameterName = substr($parameterNode->parameterName, 1);
					$type = $parameterNode->type !== null
						? $this->typeNodeResolver->resolve($parameterNode->type, $nameScope)
						: new MixedType();
					if ($parameterNode->defaultValue instanceof ConstExprNullNode) {
						$type = TypeCombinator::addNull($type);
					}
					$defaultValue = null;
					if ($parameterNode->defaultValue !== null) {
						$defaultValue = $this->constExprNodeResolver->resolve($parameterNode->defaultValue);
					}

					$parameters[$parameterName] = new MethodTagParameter(
						$type,
						$parameterNode->isReference
							? PassedByReference::createCreatesNewVariable()
							: PassedByReference::createNo(),
						$parameterNode->isVariadic || $parameterNode->defaultValue !== null,
						$parameterNode->isVariadic,
						$defaultValue
					);
				}

				$resolved[$tagValue->methodName] = new MethodTag(
					$tagValue->returnType !== null
						? $this->typeNodeResolver->resolve($tagValue->returnType, $nameScope)
						: new MixedType(),
					$tagValue->isStatic,
					$parameters
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, ExtendsTag>
	 */
	public function resolveExtendsTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@extends', '@template-extends', '@phpstan-extends'] as $tagName) {
			foreach ($phpDocNode->getExtendsTagValues($tagName) as $tagValue) {
				$resolved[$nameScope->resolveStringName($tagValue->type->type->name)] = new ExtendsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, ImplementsTag>
	 */
	public function resolveImplementsTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@implements', '@template-implements', '@phpstan-implements'] as $tagName) {
			foreach ($phpDocNode->getImplementsTagValues($tagName) as $tagValue) {
				$resolved[$nameScope->resolveStringName($tagValue->type->type->name)] = new ImplementsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, UsesTag>
	 */
	public function resolveUsesTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@use', '@template-use', '@phpstan-use'] as $tagName) {
			foreach ($phpDocNode->getUsesTagValues($tagName) as $tagValue) {
				$resolved[$nameScope->resolveStringName($tagValue->type->type->name)] = new UsesTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, TemplateTag>
	 */
	public function resolveTemplateTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		$resolvedPrefix = [];

		$prefixPriority = [
			'' => 0,
			'psalm' => 1,
			'phpstan' => 2,
		];

		foreach ($phpDocNode->getTags() as $phpDocTagNode) {
			$valueNode = $phpDocTagNode->value;
			if (!$valueNode instanceof TemplateTagValueNode) {
				continue;
			}

			$tagName = $phpDocTagNode->name;
			if (in_array($tagName, ['@template', '@psalm-template', '@phpstan-template'], true)) {
				$variance = TemplateTypeVariance::createInvariant();
			} elseif (in_array($tagName, ['@template-covariant', '@psalm-template-covariant', '@phpstan-template-covariant'], true)) {
				$variance = TemplateTypeVariance::createCovariant();
			} else {
				continue;
			}

			if (strpos($tagName, '@psalm-') === 0) {
				$prefix = 'psalm';
			} elseif (strpos($tagName, '@phpstan-') === 0) {
				$prefix = 'phpstan';
			} else {
				$prefix = '';
			}

			if (isset($resolved[$valueNode->name])) {
				$setPrefix = $resolvedPrefix[$valueNode->name];
				if ($prefixPriority[$prefix] <= $prefixPriority[$setPrefix]) {
					continue;
				}
			}

			$resolved[$valueNode->name] = new TemplateTag(
				$valueNode->name,
				$valueNode->bound !== null ? $this->typeNodeResolver->resolve($valueNode->bound, $nameScope->unsetTemplateType($valueNode->name)) : new MixedType(),
				$variance
			);
			$resolvedPrefix[$valueNode->name] = $prefix;
		}

		return $resolved;
	}

	/**
	 * @return array<string, ParamTag>
	 */
	public function resolveParamTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@param', '@psalm-param', '@phpstan-param'] as $tagName) {
			foreach ($phpDocNode->getParamTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$parameterType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
				if ($this->shouldSkipType($tagName, $parameterType)) {
					continue;
				}

				$resolved[$parameterName] = new ParamTag(
					$parameterType,
					$tagValue->isVariadic
				);
			}
		}

		return $resolved;
	}

	public function resolveReturnTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?ReturnTag
	{
		$resolved = null;

		foreach (['@return', '@psalm-return', '@phpstan-return'] as $tagName) {
			foreach ($phpDocNode->getReturnTagValues($tagName) as $tagValue) {
				$type = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
				if ($this->shouldSkipType($tagName, $type)) {
					continue;
				}
				$resolved = new ReturnTag($type, true);
			}
		}

		return $resolved;
	}

	public function resolveThrowsTags(PhpDocNode $phpDocNode, NameScope $nameScope): ?ThrowsTag
	{
		foreach (['@phpstan-throws', '@throws'] as $tagName) {
			$types = [];

			foreach ($phpDocNode->getThrowsTagValues($tagName) as $tagValue) {
				$type = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
				if ($this->shouldSkipType($tagName, $type)) {
					continue;
				}

				$types[] = $type;
			}

			if (count($types) > 0) {
				return new ThrowsTag(TypeCombinator::union(...$types));
			}
		}

		return null;
	}

	/**
	 * @return array<MixinTag>
	 */
	public function resolveMixinTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		return array_map(function (MixinTagValueNode $mixinTagValueNode) use ($nameScope): MixinTag {
			return new MixinTag(
				$this->typeNodeResolver->resolve($mixinTagValueNode->type, $nameScope)
			);
		}, $phpDocNode->getMixinTagValues());
	}

	/**
	 * @return array<string, TypeAliasTag>
	 */
	public function resolveTypeAliasTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@psalm-type', '@phpstan-type'] as $tagName) {
			foreach ($phpDocNode->getTypeAliasTagValues($tagName) as $typeAliasTagValue) {
				$alias = $typeAliasTagValue->alias;
				$typeNode = $typeAliasTagValue->type;
				$resolved[$alias] = new TypeAliasTag($alias, $typeNode, $nameScope);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, TypeAliasImportTag>
	 */
	public function resolveTypeAliasImportTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@psalm-import-type', '@phpstan-import-type'] as $tagName) {
			foreach ($phpDocNode->getTypeAliasImportTagValues($tagName) as $typeAliasImportTagValue) {
				$importedAlias = $typeAliasImportTagValue->importedAlias;
				$importedFrom = $nameScope->resolveStringName($typeAliasImportTagValue->importedFrom->name);
				$importedAs = $typeAliasImportTagValue->importedAs;
				$resolved[$importedAs ?? $importedAlias] = new TypeAliasImportTag($importedAlias, $importedFrom, $importedAs);
			}
		}

		return $resolved;
	}

	public function resolveDeprecatedTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?DeprecatedTag
	{
		foreach ($phpDocNode->getDeprecatedTagValues() as $deprecatedTagValue) {
			$description = (string) $deprecatedTagValue;
			return new DeprecatedTag($description === '' ? null : $description);
		}

		return null;
	}

	public function resolveIsDeprecated(PhpDocNode $phpDocNode): bool
	{
		$deprecatedTags = $phpDocNode->getTagsByName('@deprecated');

		return count($deprecatedTags) > 0;
	}

	public function resolveIsInternal(PhpDocNode $phpDocNode): bool
	{
		$internalTags = $phpDocNode->getTagsByName('@internal');

		return count($internalTags) > 0;
	}

	public function resolveIsFinal(PhpDocNode $phpDocNode): bool
	{
		$finalTags = $phpDocNode->getTagsByName('@final');

		return count($finalTags) > 0;
	}

	public function resolveIsPure(PhpDocNode $phpDocNode): bool
	{
		foreach ($phpDocNode->getTags() as $phpDocTagNode) {
			if (in_array($phpDocTagNode->name, ['@pure', '@psalm-pure', '@phpstan-pure'], true)) {
				return true;
			}
		}

		return false;
	}

	public function resolveIsImpure(PhpDocNode $phpDocNode): bool
	{
		foreach ($phpDocNode->getTags() as $phpDocTagNode) {
			if (in_array($phpDocTagNode->name, ['@impure', '@phpstan-impure'], true)) {
				return true;
			}
		}

		return false;
	}

	private function shouldSkipType(string $tagName, Type $type): bool
	{
		if (strpos($tagName, '@psalm-') !== 0) {
			return false;
		}

		return $this->unresolvableTypeHelper->containsUnresolvableType($type);
	}

}
