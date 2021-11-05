<?php // lint >= 8.1

namespace ClosureIntersectionTypes;

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

function(Foo&Bar $a): Foo&Bar
{

};

function(Lorem&Ipsum $a): Lorem&Ipsum
{

};

function(int&mixed $a): int&mixed
{

};
