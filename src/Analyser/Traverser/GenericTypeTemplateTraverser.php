<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Traverser;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverserCallable;

/**
 * Substitutes the class's template types in `new Foo(...)` with what the
 * constructor arguments inferred for them.
 *
 * Without a template argument frame (feature toggle off, file top level, a walk
 * started outside any body) the inferred argument is generalized as it always
 * was (`new Foo(1)` is `Foo<int>`). Under a frame the inferred argument is
 * kept exact and, during the body's observation pass, wrapped in an
 * UnresolvedTemplateArgumentType keyed by the site so the body's sends and
 * method calls can decide it; the second pass substitutes the frame's
 * resolution. An inferred argument that already carries another site's marker
 * passes through - the outer result then resolves the inner site.
 */
final class GenericTypeTemplateTraverser implements TypeTraverserCallable
{

	public function __construct(
		private readonly TemplateTypeMap $resolvedTemplateTypeMap,
		private readonly Expr $site,
		private readonly ?TemplateArgumentFrame $frame,
		private readonly bool $allowUnresolved,
	)
	{
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		if ($type instanceof TemplateType && !$type->isArgument()) {
			$newType = $this->resolvedTemplateTypeMap->getType($type->getName());
			if ($this->frame === null) {
				if ($newType === null || $newType instanceof ErrorType) {
					return $type->getDefault() ?? $type->getBound();
				}

				return TemplateTypeHelper::generalizeInferredTemplateType($type, $newType);
			}

			$initialType = $newType === null || $newType instanceof ErrorType ? null : $newType;
			// a synthetic site (the parent constructor's `new`) always hands out
			// markers - the real site re-keys and resolves them
			$synthetic = $this->site->getAttribute(TemplateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE) === true;
			if ($synthetic || ($this->allowUnresolved && $this->frame->isObserving())) {
				if ($initialType instanceof UnresolvedTemplateArgumentType) {
					return $initialType;
				}

				$marker = new UnresolvedTemplateArgumentType($this->site, $type, $initialType);
				$this->frame->noteSite($marker);

				return $marker;
			}

			return $this->frame->resolve($this->site, $type->getName()) ?? $initialType ?? $this->frame->resolveOrUnconstrained($this->site, $type);
		}

		return $traverse($type);
	}

}
