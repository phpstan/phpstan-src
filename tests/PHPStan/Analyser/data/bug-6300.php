<?php

namespace Bug6300;

use AllowDynamicProperties;

/**
 * @mixin Bar
 */
#[AllowDynamicProperties]
class Foo
{

}

/**
 * @mixin Foo
 */
#[AllowDynamicProperties]
class Bar
{

}

function (Bar $b): void
{
	$b->get();
	echo $b->fooProp;
};

