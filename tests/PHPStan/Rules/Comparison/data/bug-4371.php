<?php

namespace Bug4371;

class Foo {
}

class Bar extends Foo {

}

if(is_a(Bar::class, Foo::class)) { // should be reported
	echo "This will never be true";
} else {
	echo "NO";
}

if(is_a(Bar::class, Foo::class, false)) { // should be reported
	echo "This will never be true";
} else {
	echo "NO";
}
