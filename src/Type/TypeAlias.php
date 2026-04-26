<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function array_map;
use function array_values;
use function count;

final class TypeAlias
{

	private ?Type $resolvedType = null;

	/**
	 * @param TemplateTagValueNode[] $templateTagValueNodes
	 */
	public function __construct(
		private TypeNode $typeNode,
		private NameScope $nameScope,
		private array $templateTagValueNodes = [],
		private string $aliasName = '',
	)
	{
	}

	public static function invalid(): self
	{
		$self = new self(new IdentifierTypeNode('*ERROR*'), new NameScope(null, []));
		$self->resolvedType = new CircularTypeAliasErrorType();
		return $self;
	}

	/**
	 * Returns the type with TemplateType placeholders for any declared template params.
	 * For non-generic aliases this is the fully-resolved concrete type.
	 */
	public function resolve(TypeNodeResolver $typeNodeResolver): Type
	{
		if ($this->resolvedType !== null) {
			return $this->resolvedType;
		}

		$nameScope = $this->nameScope;

		if (count($this->templateTagValueNodes) > 0) {
			$nameScope = $this->buildNameScopeWithTemplates($typeNodeResolver, $nameScope);
		}

		return $this->resolvedType = $typeNodeResolver->resolve($this->typeNode, $nameScope);
	}

	/** Whether this alias was declared with type parameters (e.g. @phpstan-type Foo<T>). */
	public function isGeneric(): bool
	{
		return count($this->templateTagValueNodes) > 0;
	}

	/**
	 * @return TemplateTagValueNode[]
	 */
	public function getTemplateTagValueNodes(): array
	{
		return $this->templateTagValueNodes;
	}

	/**
	 * Creates a GenericTypeAliasType for this alias with the given type arguments.
	 *
	 * @param list<Type> $args Concrete or partially-resolved type arguments in parameter order.
	 */
	public function createApplicationType(TypeNodeResolver $typeNodeResolver, array $args): GenericTypeAliasType
	{
		$resolvedBody = $this->resolve($typeNodeResolver);

		$paramNames = [];
		$defaults = [];
		$boundFallbacks = [];

		foreach (array_values($this->templateTagValueNodes) as $tvn) {
			$paramNames[] = $tvn->name;
			$defaults[] = $tvn->default !== null
				? $typeNodeResolver->resolve($tvn->default, $this->nameScope)
				: null;
			$boundFallbacks[] = $tvn->bound !== null
				? $typeNodeResolver->resolve($tvn->bound, $this->nameScope)
				: new MixedType(true);
		}

		return new GenericTypeAliasType(
			$this->aliasName,
			$resolvedBody,
			$paramNames,
			$args,
			$defaults,
			$boundFallbacks,
		);
	}

	/**
	 * Builds a NameScope augmented with TemplateType placeholders for each declared template param,
	 * so the alias body can reference them (e.g. `TFilter` resolves to a TemplateType).
	 */
	private function buildNameScopeWithTemplates(TypeNodeResolver $typeNodeResolver, NameScope $nameScope): NameScope
	{
		$templateTags = [];
		foreach ($this->templateTagValueNodes as $templateTagValueNode) {
			$templateTags[$templateTagValueNode->name] = new TemplateTag(
				$templateTagValueNode->name,
				$templateTagValueNode->bound !== null
					? $typeNodeResolver->resolve($templateTagValueNode->bound, $nameScope)
					: new MixedType(true),
				$templateTagValueNode->default !== null
					? $typeNodeResolver->resolve($templateTagValueNode->default, $nameScope)
					: null,
				TemplateTypeVariance::createInvariant(),
			);
		}

		$className = $nameScope->getClassNameForTypeAlias();
		$templateTypeScope = $className !== null && $this->aliasName !== ''
			? TemplateTypeScope::createWithTypeAlias($className, $this->aliasName)
			: TemplateTypeScope::createWithAnonymousFunction();

		$templateTypeMap = new TemplateTypeMap(array_map(
			static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag),
			$templateTags,
		));

		return $nameScope->withTemplateTypeMap($templateTypeMap, $templateTags);
	}

}
