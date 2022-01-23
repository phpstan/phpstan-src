<?php

namespace Bug6300;

/**
 * @mixin Bar
 */
class Foo
{

}

/**
 * @mixin Foo
 */
class Bar
{

}

function (Bar $b): void
{
	$b->get();
	echo $b->fooProp;
};

