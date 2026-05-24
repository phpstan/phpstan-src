<?php

function () {
	if (enum_exists(\UnknownEnum\Foo::class)) {
		echo \UnknownEnum\Foo::class;
	}
	echo \UnknownEnum\Foo::class;
};
