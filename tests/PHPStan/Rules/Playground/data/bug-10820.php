<?php declare(strict_types = 1);

namespace Bug10820;

abstract class Value
{
    public function assertString(): SassString
    {
        throw new \Exception('this is not a string');
    }
}

final class SassString extends Value
{
    public function assertString(): SassString
    {
        return $this;
    }
}

class NonFinalBase
{
    protected function doSomething(): int
    {
        throw new \Exception('not implemented');
    }

    private function doPrivate(): int
    {
        throw new \Exception('not implemented');
    }
}

final class FinalClass
{
    public function doSomething(): int
    {
        throw new \Exception('not implemented');
    }
}
