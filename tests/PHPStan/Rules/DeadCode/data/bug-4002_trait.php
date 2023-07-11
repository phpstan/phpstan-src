<?php

namespace Bug4002\Trait_;

new class {
	use Foo;
};
exit;

trait Foo
{
}
