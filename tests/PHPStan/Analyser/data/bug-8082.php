<?php declare(strict_types = 1);

namespace Bug8082;

trait TraitUsesSelf
{
	use TraitUsesSelf;
}

class Foo
{
	use TraitUsesSelf;
}
