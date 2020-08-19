<?php

namespace Bug3379;

class Foo
{

	const URL = SOME_UNKNOWN_CONST . '/test';

}

function () {
	echo Foo::URL;
};
