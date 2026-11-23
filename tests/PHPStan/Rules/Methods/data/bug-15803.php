<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15803;

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

function doFoo(AbstractView $abstract, AbstractView $o):void {
	$abstract->add(object: $o); // should error because Menu has a different named arg?
}
