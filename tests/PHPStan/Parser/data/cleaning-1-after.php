<?php
namespace Cleaning;

class Foo
{
    public function doFoo()
    {
    }
}
interface Bar
{
    public function doBar();
}
class Baz
{
    public function someGenerator()
    {
        yield;
    }
    public function someGenerator2()
    {
        yield from [1, 2, 3];
    }
    public function someGenerator3()
    {
        yield;
    }
    public function someVariadics()
    {
        \func_get_args();
    }
    public function both()
    {
        yield;
        \func_get_args();
    }
}
class InlineVars
{
    public function doFoo()
    {
        yield;
        \func_get_args();
    }
}
class ContainsClosure
{
    public function doFoo()
    {
    }
    public function doBar()
    {
    }
}
