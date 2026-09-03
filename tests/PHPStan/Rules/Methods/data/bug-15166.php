<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15166;

class Model {}
class ExtendedModel extends Model {}

/** @template-covariant T of Model */
class Component {}

/** @extends Component<ExtendedModel> */
class ExtendedComponent extends Component {}

/** @template M of Model */
class ComponentBuilder
{
	/** @param M $model */
	public function __construct(public readonly Model $model) {}

	/** @param Component<M> $component */
	public function accept(Component $component): void {}
}

function fromParameter(ExtendedModel $model): void
{
	$builder = new ComponentBuilder($model);
	$builder->accept(new ExtendedComponent()); // all good
}

function fromNew(): void
{
	$builder = new ComponentBuilder(new ExtendedModel());
	$builder->accept(new ExtendedComponent()); // reported
}

/** @template T of Model */
class Invariant
{
	/** @param T $model */
	public function __construct(public readonly Model $model) {}

	/** @param Component<T> $component */
	public function accept(Component $component): void {}
}

/** @template-contravariant T of Model */
class Contravariant {}

/** @extends Contravariant<ExtendedModel> */
class ExtendedContravariant extends Contravariant {}

/** @template T of Model */
class ContravariantBuilder
{
	/** @param T $model */
	public function __construct(public readonly Model $model) {}

	/** @param Contravariant<T> $component */
	public function accept(Contravariant $component): void {}
}

function otherVariances(): void
{
	$invariant = new Invariant(new ExtendedModel());
	$invariant->accept(new ExtendedComponent());

	$contravariant = new ContravariantBuilder(new ExtendedModel());
	$contravariant->accept(new ExtendedContravariant());
}

/** @template T */
class Setter
{
	/** @param T $value */
	public function __construct(private $value) {}

	/** @param T $value */
	public function set($value): void {}
}

/** @template-covariant T of Model */
class ComponentOf
{
	/** @param T $model */
	public function __construct(public readonly Model $model) {}
}

/** @param ComponentOf<ExtendedModel> $componentOf */
function nestedInGenericType(ComponentOf $componentOf): void
{
	$setter = new Setter(new ComponentOf(new ExtendedModel()));
	$setter->set($componentOf);
}
