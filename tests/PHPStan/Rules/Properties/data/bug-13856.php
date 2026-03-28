<?php // lint >= 8.1

namespace Bug13856;

use SplObjectStorage;

class foo
{
	/** @var SplObjectStorage<object, mixed> */
    private readonly SplObjectStorage $store;

    public function __construct()
    {
        $this->store = new SplObjectStorage();
        $this->store[(object) ['foo' => 'bar']] = true;
        unset($this->store[(object) ['foo' => 'bar']]);
    }
}

class foo2
{
    /** @var array<int, bool> */
    private readonly array $store;

    public function __construct()
    {
        $this->store[1] = true;
        $this->store[2] = false;
    }
}

class foo3
{
    private readonly object $store;

    public function __construct()
    {
        $this->store = new SplObjectStorage();
        $this->store[(object) ['foo' => 'bar']] = true;
        unset($this->store[(object) ['foo' => 'bar']]);
    }
}
