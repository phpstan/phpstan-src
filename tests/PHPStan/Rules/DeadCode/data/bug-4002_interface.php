<?php

namespace Bug4002\Interface_;

echo Foo::BAR;
exit;

interface Foo
{
	const BAR = 1;
}
