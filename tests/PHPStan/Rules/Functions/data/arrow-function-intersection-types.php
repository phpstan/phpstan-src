<?php // lint >= 8.1

namespace ArrowFunctionIntersectionTypes;

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

fn(Foo&Bar $a): Foo&Bar => 1;

fn(Lorem&Ipsum $a): Lorem&Ipsum => 2;

fn(int&mixed $a): int&mixed => 3;
