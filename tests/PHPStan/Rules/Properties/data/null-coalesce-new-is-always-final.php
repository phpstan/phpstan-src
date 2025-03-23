<?php

namespace NullCoalesceIsAlwaysFinal;

class Foo
{

}

function (): void {
	$foo = new Foo();
	echo $foo->bar ?? 'no';
};
