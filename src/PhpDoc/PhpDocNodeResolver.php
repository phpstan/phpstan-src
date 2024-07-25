<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\PhpDoc\Tag\AssertTagParameter;
use PHPStan\PhpDoc\Tag\DeprecatedTag;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MethodTagParameter;
use PHPStan\PhpDoc\Tag\MixinTag;
use PHPStan\PhpDoc\Tag\ParamClosureThisTag;
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
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MixinTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_reverse;
use function count;
use function in_array;
use function method_exists;
use function str_starts_with;
use function substr;

final class PhpDocNodeResolver
{

	public function __construct(
		private TypeNodeResolver $typeNodeResolver,
		private ConstExprNodeResolver $constExprNodeResolver,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
	)
	{
	}

	/**
	 * @return array<(string|int), VarTag>
	 */
	public function resolveVarTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		$resolvedByTag = [];
		foreach (['@var', '@phan-var', '@psalm-var', '@phpstan-var'] as $tagName) {
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
					$propertyType,
					$propertyType,
				);
			}
		}

		foreach (['@property-read', '@phpstan-property-read'] as $tagName) {
			foreach ($phpDocNode->getPropertyReadTagValues($tagName) as $tagValue) {
				$propertyName = substr($tagValue->propertyName, 1);
				$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

				$writableType = null;
				if (array_key_exists($propertyName, $resolved)) {
					$writableType = $resolved[$propertyName]->getWritableType();
				}

				$resolved[$propertyName] = new PropertyTag(
					$propertyType,
					$propertyType,
					$writableType,
				);
			}
		}

		foreach (['@property-write', '@phpstan-property-write'] as $tagName) {
			foreach ($phpDocNode->getPropertyWriteTagValues($tagName) as $tagValue) {
				$propertyName = substr($tagValue->propertyName, 1);
				$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

				$readableType = null;
				if (array_key_exists($propertyName, $resolved)) {
					$readableType = $resolved[$propertyName]->getReadableType();
				}

				$resolved[$propertyName] = new PropertyTag(
					$readableType ?? $propertyType,
					$readableType,
					$propertyType,
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
		$originalNameScope = $nameScope;

		foreach (['@method', '@phan-method', '@psalm-method', '@phpstan-method'] as $tagName) {
			foreach ($phpDocNode->getMethodTagValues($tagName) as $tagValue) {
				$nameScope = $originalNameScope;
				$templateTags = [];

				if (count($tagValue->templateTypes) > 0 && $nameScope->getClassName() !== null) {
					foreach ($tagValue->templateTypes as $templateType) {
						$templateTags[$templateType->name] = new TemplateTag(
							$templateType->name,
							$templateType->bound !== null
								? $this->typeNodeResolver->resolve($templateType->bound, $nameScope)
								: new MixedType(),
							TemplateTypeVariance::createInvariant(),
						);
					}

					$templateTypeScope = TemplateTypeScope::createWithMethod($nameScope->getClassName(), $tagValue->methodName);
					$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
					$nameScope = $nameScope->withTemplateTypeMap($templateTypeMap);
				}

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
						$defaultValue,
					);
				}

				$resolved[$tagValue->methodName] = new MethodTag(
					$tagValue->returnType !== null
						? $this->typeNodeResolver->resolve($tagValue->returnType, $nameScope)
						: new MixedType(),
					$tagValue->isStatic,
					$parameters,
					$templateTags,
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

		foreach (['@extends', '@phan-extends', '@phan-inherits', '@template-extends', '@phpstan-extends'] as $tagName) {
			foreach ($phpDocNode->getExtendsTagValues($tagName) as $tagValue) {
				$resolved[$nameScope->resolveStringName($tagValue->type->type->name)] = new ExtendsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope),
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
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope),
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
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope),
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
			'phan' => 1,
			'psalm' => 2,
			'phpstan' => 3,
		];

		foreach ($phpDocNode->getTags() as $phpDocTagNode) {
			$valueNode = $phpDocTagNode->value;
			if (!$valueNode instanceof TemplateTagValueNode) {
				continue;
			}

			$tagName = $phpDocTagNode->name;
			if (in_array($tagName, ['@template', '@phan-template', '@psalm-template', '@phpstan-template'], true)) {
				$variance = TemplateTypeVariance::createInvariant();
			} elseif (in_array($tagName, ['@template-covariant', '@psalm-template-covariant', '@phpstan-template-covariant'], true)) {
				$variance = TemplateTypeVariance::createCovariant();
			} elseif (in_array($tagName, ['@template-contravariant', '@psalm-template-contravariant', '@phpstan-template-contravariant'], true)) {
				$variance = TemplateTypeVariance::createContravariant();
			} else {
				continue;
			}

			if (str_starts_with($tagName, '@phan-')) {
				$prefix = 'phan';
			} elseif (str_starts_with($tagName, '@psalm-')) {
				$prefix = 'psalm';
			} elseif (str_starts_with($tagName, '@phpstan-')) {
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
				$valueNode->bound !== null ? $this->typeNodeResolver->resolve($valueNode->bound, $nameScope->unsetTemplateType($valueNode->name)) : new MixedType(true),
				$variance,
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

		foreach (['@param', '@phan-param', '@psalm-param', '@phpstan-param'] as $tagName) {
			foreach ($phpDocNode->getParamTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$parameterType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
				if ($this->shouldSkipType($tagName, $parameterType)) {
					continue;
				}

				$resolved[$parameterName] = new ParamTag(
					$parameterType,
					$tagValue->isVariadic,
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, ParamOutTag>
	 */
	public function resolveParamOutTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		if (!method_exists($phpDocNode, 'getParamOutTypeTagValues')) {
			return [];
		}

		$resolved = [];

		foreach (['@param-out', '@psalm-param-out', '@phpstan-param-out'] as $tagName) {
			foreach ($phpDocNode->getParamOutTypeTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$parameterType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
				if ($this->shouldSkipType($tagName, $parameterType)) {
					continue;
				}

				$resolved[$parameterName] = new ParamOutTag(
					$parameterType,
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, bool>
	 */
	public function resolveParamImmediatelyInvokedCallable(PhpDocNode $phpDocNode): array
	{
		$parameters = [];
		foreach (['@param-immediately-invoked-callable', '@phpstan-param-immediately-invoked-callable'] as $tagName) {
			foreach ($phpDocNode->getParamImmediatelyInvokedCallableTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$parameters[$parameterName] = true;
			}
		}
		foreach (['@param-later-invoked-callable', '@phpstan-param-later-invoked-callable'] as $tagName) {
			foreach ($phpDocNode->getParamLaterInvokedCallableTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$parameters[$parameterName] = false;
			}
		}

		return $parameters;
	}

	/**
	 * @return array<string, ParamClosureThisTag>
	 */
	public function resolveParamClosureThisTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$closureThisTypes = [];
		foreach (['@param-closure-this', '@phpstan-param-closure-this'] as $tagName) {
			foreach ($phpDocNode->getParamClosureThisTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$closureThisTypes[$parameterName] = new ParamClosureThisTag($this->typeNodeResolver->resolve($tagValue->type, $nameScope));
			}
		}

		return $closureThisTypes;
	}

	public function resolveReturnTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?ReturnTag
	{
		$resolved = null;

		foreach (['@return', '@phan-return', '@phan-real-return', '@psalm-return', '@phpstan-return'] as $tagName) {
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
		return array_map(fn (MixinTagValueNode $mixinTagValueNode): MixinTag => new MixinTag(
			$this->typeNodeResolver->resolve($mixinTagValueNode->type, $nameScope),
		), $phpDocNode->getMixinTagValues());
	}

	/**
	 * @return array<RequireExtendsTag>
	 */
	public function resolveRequireExtendsTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@psalm-require-extends', '@phpstan-require-extends'] as $tagName) {
			foreach ($phpDocNode->getRequireExtendsTagValues($tagName) as $tagValue) {
				$resolved[] = new RequireExtendsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope),
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<RequireImplementsTag>
	 */
	public function resolveRequireImplementsTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@psalm-require-implements', '@phpstan-require-implements'] as $tagName) {
			foreach ($phpDocNode->getRequireImplementsTagValues($tagName) as $tagValue) {
				$resolved[] = new RequireImplementsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope),
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, TypeAliasTag>
	 */
	public function resolveTypeAliasTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@phan-type', '@psalm-type', '@phpstan-type'] as $tagName) {
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

	/**
	 * @return AssertTag[]
	 */
	public function resolveAssertTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		foreach (['@phpstan', '@psalm', '@phan'] as $prefix) {
			$resolved = array_merge(
				$this->resolveAssertTagsFor($phpDocNode, $nameScope, $prefix . '-assert', AssertTag::NULL),
				$this->resolveAssertTagsFor($phpDocNode, $nameScope, $prefix . '-assert-if-true', AssertTag::IF_TRUE),
				$this->resolveAssertTagsFor($phpDocNode, $nameScope, $prefix . '-assert-if-false', AssertTag::IF_FALSE),
			);

			if (count($resolved) > 0) {
				return $resolved;
			}
		}

		return [];
	}

	/**
	 * @param AssertTag::NULL|AssertTag::IF_TRUE|AssertTag::IF_FALSE $if
	 * @return AssertTag[]
	 */
	private function resolveAssertTagsFor(PhpDocNode $phpDocNode, NameScope $nameScope, string $tagName, string $if): array
	{
		$resolved = [];

		foreach ($phpDocNode->getAssertTagValues($tagName) as $assertTagValue) {
			$type = $this->typeNodeResolver->resolve($assertTagValue->type, $nameScope);
			$parameter = new AssertTagParameter($assertTagValue->parameter, null, null);
			$resolved[] = new AssertTag($if, $type, $parameter, $assertTagValue->isNegated, $assertTagValue->isEquality ?? false, true);
		}

		foreach ($phpDocNode->getAssertPropertyTagValues($tagName) as $assertTagValue) {
			$type = $this->typeNodeResolver->resolve($assertTagValue->type, $nameScope);
			$parameter = new AssertTagParameter($assertTagValue->parameter, $assertTagValue->property, null);
			$resolved[] = new AssertTag($if, $type, $parameter, $assertTagValue->isNegated, $assertTagValue->isEquality ?? false, true);
		}

		foreach ($phpDocNode->getAssertMethodTagValues($tagName) as $assertTagValue) {
			$type = $this->typeNodeResolver->resolve($assertTagValue->type, $nameScope);
			$parameter = new AssertTagParameter($assertTagValue->parameter, null, $assertTagValue->method);
			$resolved[] = new AssertTag($if, $type, $parameter, $assertTagValue->isNegated, $assertTagValue->isEquality ?? false, true);
		}

		return $resolved;
	}

	public function resolveSelfOutTypeTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?SelfOutTypeTag
	{
		if (!method_exists($phpDocNode, 'getSelfOutTypeTagValues')) {
			return null;
		}

		foreach (['@phpstan-this-out', '@phpstan-self-out', '@psalm-this-out', '@psalm-self-out'] as $tagName) {
			foreach ($phpDocNode->getSelfOutTypeTagValues($tagName) as $selfOutTypeTagValue) {
				$type = $this->typeNodeResolver->resolve($selfOutTypeTagValue->type, $nameScope);
				return new SelfOutTypeTag($type);
			}
		}

		return null;
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

	public function resolveIsNotDeprecated(PhpDocNode $phpDocNode): bool
	{
		$notDeprecatedTags = $phpDocNode->getTagsByName('@not-deprecated');

		return count($notDeprecatedTags) > 0;
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
			if (in_array($phpDocTagNode->name, ['@pure', '@phan-pure', '@phan-side-effect-free', '@psalm-pure', '@phpstan-pure'], true)) {
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

	public function resolveIsReadOnly(PhpDocNode $phpDocNode): bool
	{
		foreach (['@readonly', '@phan-read-only', '@psalm-readonly', '@phpstan-readonly', '@phpstan-readonly-allow-private-mutation', '@psalm-readonly-allow-private-mutation'] as $tagName) {
			$tags = $phpDocNode->getTagsByName($tagName);

			if (count($tags) > 0) {
				return true;
			}
		}

		return false;
	}

	public function resolveIsImmutable(PhpDocNode $phpDocNode): bool
	{
		foreach (['@immutable', '@phan-immutable', '@psalm-immutable', '@phpstan-immutable'] as $tagName) {
			$tags = $phpDocNode->getTagsByName($tagName);

			if (count($tags) > 0) {
				return true;
			}
		}

		return false;
	}

	public function resolveHasConsistentConstructor(PhpDocNode $phpDocNode): bool
	{
		foreach (['@consistent-constructor', '@phpstan-consistent-constructor', '@psalm-consistent-constructor'] as $tagName) {
			$tags = $phpDocNode->getTagsByName($tagName);

			if (count($tags) > 0) {
				return true;
			}
		}

		return false;
	}

	public function resolveAcceptsNamedArguments(PhpDocNode $phpDocNode): bool
	{
		return count($phpDocNode->getTagsByName('@no-named-arguments')) === 0;
	}

	private function shouldSkipType(string $tagName, Type $type): bool
	{
		if (!str_starts_with($tagName, '@psalm-')) {
			return false;
		}

		return $this->unresolvableTypeHelper->containsUnresolvableType($type);
	}

	public function resolveAllowPrivateMutation(PhpDocNode $phpDocNode): bool
	{
		foreach (['@phpstan-readonly-allow-private-mutation', '@phpstan-allow-private-mutation', '@psalm-readonly-allow-private-mutation', '@psalm-allow-private-mutation'] as $tagName) {
			$tags = $phpDocNode->getTagsByName($tagName);

			if (count($tags) > 0) {
				return true;
			}
		}

		return false;
	}

}
