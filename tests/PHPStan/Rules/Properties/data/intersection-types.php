<?php

namespace PropertyIntersectionTypes;

interface Foo
{

}

interface Bar
{

}

class Lorem
{

}

class Ipsum
{

}

class Test
{

	private Foo&Bar $prop1;

	private Lorem&Ipsum $prop2;

	private int&mixed $prop3;

}
