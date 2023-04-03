<?php

namespace Discussion9053;

use function PHPStan\Testing\assertType;

/**
 * @template-covariant TChild of ChildInterface
 */
interface ModelInterface {
	/**
	 * @return TChild[]
	 */
	public function getChildren(): array;
}

/**
 * @implements ModelInterface<Child>
 */
class Model implements ModelInterface
{
	/**
	 * @var Child[]
	 */
	public array $children;

	public function getChildren(): array
	{
		return $this->children;
	}
}

/**
 * @template-covariant T of ModelInterface
 */
interface ChildInterface {
	/**
	 * @return T
	 */
	public function getModel(): ModelInterface;
}


/**
 * @implements ChildInterface<Model>
 */
class Child implements ChildInterface
{
	public function __construct(private Model $model)
	{
	}

	public function getModel(): Model
	{
		return $this->model;
	}
}

/**
 * @template-covariant T of ModelInterface
 */
class Helper
{
	/**
	 * @param T $model
	 */
	public function __construct(private ModelInterface $model)
	{}

	/**
	 * @return template-type<T, ModelInterface, 'TChild'>
	 */
	public function getFirstChildren(): ChildInterface
	{
		$firstChildren = $this->model->getChildren()[0] ?? null;

		if (!$firstChildren) {
			throw new \RuntimeException('No first child found.');
		}

		return $firstChildren;
	}
}

class Other {
	/**
	 * @template TChild of ChildInterface
	 * @template TModel of ModelInterface<TChild>
	 * @param Helper<TModel> $helper
	 * @return TChild
	 */
	public function getFirstChildren(Helper $helper): ChildInterface {
		$child = $helper->getFirstChildren();
		assertType('TChild of Discussion9053\ChildInterface (method Discussion9053\Other::getFirstChildren(), argument)', $child);

		return $child;
	}
}

function (): void {
	$model = new Model();
	$helper = new Helper($model);
	assertType('Discussion9053\Helper<Discussion9053\Model>', $helper);
	$child = $helper->getFirstChildren();
	assertType('Discussion9053\Child', $child);

	$other = new Other();
	$child2 = $other->getFirstChildren($helper);
	assertType('Discussion9053\Child', $child2);
};
