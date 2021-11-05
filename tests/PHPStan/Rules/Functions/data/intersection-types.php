<?php // lint >= 8.1

namespace FunctionIntersectionTypes;

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

function doFoo(Foo&Bar $a): Foo&Bar
{

}

function doBar(Lorem&Ipsum $a): Lorem&Ipsum
{

}

function doBaz(int&mixed $a): int&mixed
{

}
