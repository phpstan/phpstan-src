<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15166Properties;

class Model {}
class ExtendedModel extends Model {}

/** @template-covariant T of Model */
class Component {}

/** @extends Component<ExtendedModel> */
class ExtendedComponent extends Component {}

/** @template M of Model */
class Holder
{
	/** @var Component<M>|null */
	public ?Component $component = null;

	/** @param M $model */
	public function __construct(public readonly Model $model) {}
}

function fromParameter(ExtendedModel $model): void
{
	$holder = new Holder($model);
	$holder->component = new ExtendedComponent();
}

function fromNew(): void
{
	$holder = new Holder(new ExtendedModel());
	$holder->component = new ExtendedComponent();
}
