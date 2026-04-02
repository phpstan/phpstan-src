<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function implode;
use function sprintf;

/**
 * Represents a generic @phpstan-type alias applied to concrete (or partially-resolved)
 * type arguments. For example, {@code Filter<int>} where {@code @phpstan-type Filter<TItem>} is
 * declared expands lazily to the alias body with TItem substituted.
 *
 * Mirrors the role of GenericObjectType for classes: GenericObjectType is a class constructor
 * applied to type args; GenericTypeAliasType is a type alias applied to type args.
 *
 * Implements LateResolvableType so TypeUtils::resolveLateResolvableTypes() expands it at the
 * right moment without leaking TemplateType placeholders to the rest of the type system.
 */
final class GenericTypeAliasType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @param list<string>    $paramNames     Ordered parameter names from the alias declaration.
	 * @param list<Type>      $args           Supplied type arguments (may be shorter than paramNames
	 *                                         when trailing params are covered by defaults).
	 * @param list<Type|null> $defaults       Per-param declared default type; null when the param has no default.
	 * @param list<Type>      $boundFallbacks Per-param bound type used when both arg and default are absent.
	 */
	public function __construct(
		private readonly string $aliasName,
		private readonly Type $resolvedBody,
		private readonly array $paramNames,
		private readonly array $args,
		private readonly array $defaults,
		private readonly array $boundFallbacks,
	)
	{
	}

	public function getAliasName(): string
	{
		return $this->aliasName;
	}

	/**
	 * Returns the names of required params (no declared default) that were not supplied as args.
	 * A non-empty list means this is a "raw" usage of a generic alias that should be reported.
	 *
	 * @return list<string>
	 */
	public function getMissingRequiredParamNames(): array
	{
		$missing = [];
		foreach ($this->paramNames as $i => $name) {
			if (isset($this->args[$i]) || $this->defaults[$i] !== null) {
				continue;
			}

			$missing[] = $name;
		}

		return $missing;
	}

	public function getReferencedClasses(): array
	{
		$classes = $this->resolvedBody->getReferencedClasses();
		foreach ($this->args as $arg) {
			$classes = array_merge($classes, $arg->getReferencedClasses());
		}

		return array_unique($classes);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$refs = [];
		foreach ($this->args as $arg) {
			$refs = array_merge($refs, $arg->getReferencedTemplateTypes($positionVariance));
		}

		return $refs;
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if ($this->aliasName !== $type->aliasName || count($this->args) !== count($type->args)) {
			return false;
		}

		foreach ($this->args as $i => $arg) {
			if (!$arg->equals($type->args[$i])) {
				return false;
			}
		}

		return true;
	}

	public function describe(VerbosityLevel $level): string
	{
		if ($this->args === []) {
			return $this->aliasName;
		}

		return sprintf(
			'%s<%s>',
			$this->aliasName,
			implode(', ', array_map(static fn (Type $t) => $t->describe($level), $this->args)),
		);
	}

	public function isResolvable(): bool
	{
		foreach ($this->args as $arg) {
			if (TypeUtils::containsTemplateType($arg)) {
				return false;
			}
		}

		foreach (array_keys($this->paramNames) as $i) {
			if (!isset($this->args[$i]) && $this->defaults[$i] === null) {
				return false;
			}
		}

		return true;
	}

	protected function getResult(): Type
	{
		$map = [];
		foreach ($this->paramNames as $i => $name) {
			$map[$name] = $this->args[$i] ?? $this->defaults[$i] ?? $this->boundFallbacks[$i];
		}

		return TemplateTypeHelper::resolveTemplateTypes(
			$this->resolvedBody,
			new TemplateTypeMap($map),
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		);
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$newArgs = array_map($cb, $this->args);

		foreach ($this->args as $i => $arg) {
			if ($arg !== $newArgs[$i]) {
				return new self(
					$this->aliasName,
					$this->resolvedBody,
					$this->paramNames,
					$newArgs,
					$this->defaults,
					$this->boundFallbacks,
				);
			}
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$newArgs = [];
		$changed = false;
		foreach ($this->args as $i => $arg) {
			$newArg = isset($right->args[$i]) ? $cb($arg, $right->args[$i]) : $arg;
			if ($newArg !== $arg) {
				$changed = true;
			}

			$newArgs[] = $newArg;
		}

		if (!$changed) {
			return $this;
		}

		return new self(
			$this->aliasName,
			$this->resolvedBody,
			$this->paramNames,
			$newArgs,
			$this->defaults,
			$this->boundFallbacks,
		);
	}

	public function toPhpDocNode(): TypeNode
	{
		if ($this->args === []) {
			return new IdentifierTypeNode($this->aliasName);
		}

		return new GenericTypeNode(
			new IdentifierTypeNode($this->aliasName),
			array_map(static fn (Type $t) => $t->toPhpDocNode(), $this->args),
		);
	}

}
