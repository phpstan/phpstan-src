<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\DeprecatedTag;
use PHPStan\PhpDoc\Tag\ExtendsTag;
use PHPStan\PhpDoc\Tag\ImplementsTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MethodTagParameter;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\Tag\ThrowsTag;
use PHPStan\PhpDoc\Tag\UsesTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class PhpDocNodeResolver
{

	/** @var TypeNodeResolver */
	private $typeNodeResolver;

	/** @var ConstExprNodeResolver */
	private $constExprNodeResolver;

	public function __construct(TypeNodeResolver $typeNodeResolver, ConstExprNodeResolver $constExprNodeResolver)
	{
		$this->typeNodeResolver = $typeNodeResolver;
		$this->constExprNodeResolver = $constExprNodeResolver;
	}

	public function resolve(PhpDocNode $phpDocNode, NameScope $nameScope): ResolvedPhpDocBlock
	{
		$templateTags = $this->resolveTemplateTags($phpDocNode, $nameScope);
		$templateTypeScope = $nameScope->getTemplateTypeScope();

		if ($templateTypeScope !== null) {
			$templateTypeMap = new TemplateTypeMap(array_map(static function (TemplateTag $tag) use ($templateTypeScope): Type {
				return TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag);
			}, $templateTags));
			$nameScope = $nameScope->withTemplateTypeMap(
				new TemplateTypeMap(array_merge(
					$nameScope->getTemplateTypeMap()->getTypes(),
					$templateTypeMap->getTypes()
				))
			);
		} else {
			$templateTypeMap = TemplateTypeMap::createEmpty();
		}

		return ResolvedPhpDocBlock::create(
			$this->resolveVarTags($phpDocNode, $nameScope),
			$this->resolveMethodTags($phpDocNode, $nameScope),
			$this->resolvePropertyTags($phpDocNode, $nameScope),
			$templateTags,
			$this->resolveExtendsTags($phpDocNode, $nameScope),
			$this->resolveImplementsTags($phpDocNode, $nameScope),
			$this->resolveUsesTags($phpDocNode, $nameScope),
			$this->resolveParamTags($phpDocNode, $nameScope),
			$this->resolveReturnTag($phpDocNode, $nameScope),
			$this->resolveThrowsTags($phpDocNode, $nameScope),
			$this->resolveDeprecatedTag($phpDocNode, $nameScope),
			$this->resolveIsDeprecated($phpDocNode),
			$this->resolveIsInternal($phpDocNode),
			$this->resolveIsFinal($phpDocNode),
			$templateTypeMap
		);
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return array<string|int, \PHPStan\PhpDoc\Tag\VarTag>
	 */
	private function resolveVarTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		foreach (['@phpstan-var', '@psalm-var', '@var'] as $tagName) {
			$resolved = [];
			foreach ($phpDocNode->getVarTagValues($tagName) as $tagValue) {
				if ($tagValue->variableName !== '') {
					$variableName = substr($tagValue->variableName, 1);
					$type = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);
					$resolved[$variableName] = new VarTag($type);

				} else {
					$resolved[] = new VarTag($this->typeNodeResolver->resolve($tagValue->type, $nameScope));
				}
			}

			if (count($resolved) > 0) {
				return $resolved;
			}
		}

		return [];
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return array<string, \PHPStan\PhpDoc\Tag\PropertyTag>
	 */
	private function resolvePropertyTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach ($phpDocNode->getPropertyTagValues() as $tagValue) {
			$propertyName = substr($tagValue->propertyName, 1);
			$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

			$resolved[$propertyName] = new PropertyTag(
				$propertyType,
				true,
				true
			);
		}

		foreach ($phpDocNode->getPropertyReadTagValues() as $tagValue) {
			$propertyName = substr($tagValue->propertyName, 1);
			$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

			$resolved[$propertyName] = new PropertyTag(
				$propertyType,
				true,
				false
			);
		}

		foreach ($phpDocNode->getPropertyWriteTagValues() as $tagValue) {
			$propertyName = substr($tagValue->propertyName, 1);
			$propertyType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

			$resolved[$propertyName] = new PropertyTag(
				$propertyType,
				false,
				true
			);
		}

		return $resolved;
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return array<string, \PHPStan\PhpDoc\Tag\MethodTag>
	 */
	private function resolveMethodTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach ($phpDocNode->getMethodTagValues() as $tagValue) {
			$parameters = [];
			foreach ($tagValue->parameters as $parameterNode) {
				$parameterName = substr($parameterNode->parameterName, 1);
				$type = $parameterNode->type !== null ? $this->typeNodeResolver->resolve($parameterNode->type, $nameScope) : new MixedType();
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
				$tagValue->returnType !== null ? $this->typeNodeResolver->resolve($tagValue->returnType, $nameScope) : new MixedType(),
				$tagValue->isStatic,
				$parameters
			);
		}

		return $resolved;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\ExtendsTag>
	 */
	private function resolveExtendsTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@extends', '@template-extends', '@phpstan-extends'] as $tagName) {
			foreach ($phpDocNode->getExtendsTagValues($tagName) as $tagValue) {
				$resolved[$tagValue->type->type->name] = new ExtendsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\ImplementsTag>
	 */
	private function resolveImplementsTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@implements', '@template-implements', '@phpstan-implements'] as $tagName) {
			foreach ($phpDocNode->getImplementsTagValues($tagName) as $tagValue) {
				$resolved[$tagValue->type->type->name] = new ImplementsTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				);
			}
		}

		return $resolved;
	}

	/**
	 * @return array<string, \PHPStan\PhpDoc\Tag\UsesTag>
	 */
	private function resolveUsesTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@uses', '@template-use', '@phpstan-uses'] as $tagName) {
			foreach ($phpDocNode->getUsesTagValues($tagName) as $tagValue) {
				$resolved[$tagValue->type->type->name] = new UsesTag(
					$this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				);
			}
		}

		return $resolved;
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return array<string, \PHPStan\PhpDoc\Tag\TemplateTag>
	 */
	private function resolveTemplateTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@template', '@psalm-template', '@phpstan-template'] as $tagName) {
			foreach ($phpDocNode->getTemplateTagValues($tagName) as $tagValue) {
				$resolved[$tagValue->name] = new TemplateTag(
					$tagValue->name,
					$tagValue->bound !== null ? $this->typeNodeResolver->resolve($tagValue->bound, $nameScope) : new MixedType(),
					TemplateTypeVariance::createInvariant()
				);
			}
		}

		foreach (['@template-covariant', '@psalm-template-covariant', '@phpstan-template-covariant'] as $tagName) {
			foreach ($phpDocNode->getTemplateTagValues($tagName) as $tagValue) {
				$resolved[$tagValue->name] = new TemplateTag(
					$tagValue->name,
					$tagValue->bound !== null ? $this->typeNodeResolver->resolve($tagValue->bound, $nameScope) : new MixedType(),
					TemplateTypeVariance::createCovariant()
				);
			}
		}

		return $resolved;
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return array<string, \PHPStan\PhpDoc\Tag\ParamTag>
	 */
	private function resolveParamTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach (['@param', '@psalm-param', '@phpstan-param'] as $tagName) {
			foreach ($phpDocNode->getParamTagValues($tagName) as $tagValue) {
				$parameterName = substr($tagValue->parameterName, 1);
				$parameterType = $this->typeNodeResolver->resolve($tagValue->type, $nameScope);

				if ($tagValue->isVariadic) {
					if (!$parameterType instanceof ArrayType) {
						$parameterType = new ArrayType(new IntegerType(), $parameterType);

					} elseif ($parameterType->getKeyType() instanceof MixedType) {
						$parameterType = new ArrayType(new IntegerType(), $parameterType->getItemType());
					}
				}

				$resolved[$parameterName] = new ParamTag(
					$parameterType,
					$tagValue->isVariadic
				);
			}
		}

		return $resolved;
	}

	private function resolveReturnTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?\PHPStan\PhpDoc\Tag\ReturnTag
	{
		$resolved = null;

		foreach (['@return', '@psalm-return', '@phpstan-return'] as $tagName) {
			foreach ($phpDocNode->getReturnTagValues($tagName) as $tagValue) {
				$resolved = new ReturnTag($this->typeNodeResolver->resolve($tagValue->type, $nameScope));
			}
		}

		return $resolved;
	}

	private function resolveThrowsTags(PhpDocNode $phpDocNode, NameScope $nameScope): ?\PHPStan\PhpDoc\Tag\ThrowsTag
	{
		$types = array_map(function (ThrowsTagValueNode $throwsTagValue) use ($nameScope): Type {
			return $this->typeNodeResolver->resolve($throwsTagValue->type, $nameScope);
		}, $phpDocNode->getThrowsTagValues());

		if (count($types) === 0) {
			return null;
		}

		return new ThrowsTag(TypeCombinator::union(...$types));
	}

	private function resolveDeprecatedTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?\PHPStan\PhpDoc\Tag\DeprecatedTag
	{
		foreach ($phpDocNode->getDeprecatedTagValues() as $deprecatedTagValue) {
			$description = (string) $deprecatedTagValue;
			return new DeprecatedTag($description === '' ? null : $description);
		}

		return null;
	}

	private function resolveIsDeprecated(PhpDocNode $phpDocNode): bool
	{
		$deprecatedTags = $phpDocNode->getTagsByName('@deprecated');

		return count($deprecatedTags) > 0;
	}

	private function resolveIsInternal(PhpDocNode $phpDocNode): bool
	{
		$internalTags = $phpDocNode->getTagsByName('@internal');

		return count($internalTags) > 0;
	}

	private function resolveIsFinal(PhpDocNode $phpDocNode): bool
	{
		$finalTags = $phpDocNode->getTagsByName('@final');

		return count($finalTags) > 0;
	}

}
