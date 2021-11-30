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
        /** @var Test */
        /** @var Test2 */
        /** @var Test3 */
        /** @phpstan-var Test */
        /** @psalm-var Test */
        yield;
        \func_get_args();
    }
}
