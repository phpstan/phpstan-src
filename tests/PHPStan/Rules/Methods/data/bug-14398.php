<?php // lint >= 8.0

namespace Bug14398;

class BaseProtectedMethod
{
    protected function calculate(): int
    {
        return 42;
    }
}

class ChildPrivateOverridesProtected extends BaseProtectedMethod
{
    // PHPStan: Private method … overriding protected method … should be protected or public.
    #[Override]
    private function calculate(): int
    {
        return 99;
    }
}
