<?php

declare(strict_types=1);

namespace Bug10987;

class Base
{
	/** @var string */
	public $layout;
}

class A extends Base
{
	public $layout = null;
}

class B extends Base
{
	public $layout = NULL;
}
