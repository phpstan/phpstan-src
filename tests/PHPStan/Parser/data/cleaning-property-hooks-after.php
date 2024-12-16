<?php
namespace CleaningPropertyHooks;

class Foo
{
    public int $i {
        get {
            $this->i;
        }
    }
}
class FooParam
{
    public function __construct(public int $i {
        get {
            $this->i;
        }
    })
    {
    }
}
