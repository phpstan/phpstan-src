<?php

function () {
	if (class_exists(\UnknownClass\Foo::class)) {
		echo \UnknownClass\Foo::class;
	}
};

function () {
	if (interface_exists(\UnknownClass\Foo::class)) {
		echo \UnknownClass\Foo::class;
	}
};

function () {
	if (trait_exists(\UnknownClass\Foo::class)) {
		echo \UnknownClass\Foo::class;
	}
};

function () {
	if (class_exists(\UnknownClass\Foo::class)) {
		echo \UnknownClass\Foo::class;
		echo \UnknownClass\Bar::class; // error
	} else {
		echo \UnknownClass\Foo::class; // error
	}

	echo \UnknownClass\Foo::class; // error
};
