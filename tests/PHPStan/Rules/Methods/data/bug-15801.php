<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15801;

class AbstractView
{
    /**
     * @return ($object is View ? View : self)
     */
    public function add(self $object): self
    {
		return new View();
	}
}

class View extends AbstractView
{
    /**
     * @param AbstractView $object
     */
    #[\Override]
    public function add($object): AbstractView
    {
        return parent::add($object);
	}
}

class Menu extends View
{
    #[\Override]
    public function add($seed): AbstractView
    {
        return parent::add($seed);
    }
}

class SwappedParent
{
	/**
	 * @return ($a is int ? int : string)
	 */
	public function foo(mixed $a, mixed $b): int|string
	{
		return 1;
	}
}

class SwappedChild extends SwappedParent
{
	/**
	 * @return ($a is int ? int : string)
	 */
	public function foo(mixed $b, mixed $a): int|string
	{
		return 1;
	}
}
