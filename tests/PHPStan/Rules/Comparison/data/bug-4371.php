<?php

namespace Bug4371;

class Foo {
}

class Bar extends Foo {

}

$a = is_a(Bar::class, Foo::class); // should be reported

$a = is_a(Bar::class, Foo::class, true); // should be fine
