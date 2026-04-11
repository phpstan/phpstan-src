<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug13473;

class Foo {
    private(set) int $bar {
        get => $this->bar;
        set(int $bar) {
            if (isset($this->bar)) {
                throw new \Exception('bar is set');
            }
            $this->bar = $bar;
        }
    }

    public function __construct(int $bar)
    {
        $this->bar = $bar;
    }
}

$foo = new Foo(10);

class Bar {
    private(set) int $bar = 1 {
        get => $this->bar;
        set(int $bar) {
            if (isset($this->bar)) {
                throw new \Exception('bar is set');
            }
            $this->bar = $bar;
        }
    }

    public function __construct(int $bar)
    {
        $this->bar = $bar;
    }
}

class Qux {
    private(set) int $foo;
    private(set) int $bar {
        get => $this->bar;
        set(int $bar) {
            if (isset($this->foo)) { // $foo has no default, could be uninitialized - no error
                throw new \Exception('foo is set');
            }
            $this->bar = $bar;
        }
    }

    public function __construct(int $bar)
    {
        $this->bar = $bar;
        $this->foo = 42;
    }
}

class Baz {
    private(set) int $foo = 5;
    private(set) int $bar {
        get => $this->bar;
        set(int $bar) {
            if (isset($this->foo)) { // $foo has default value, always initialized - should error
                echo 'foo is set';
            }
            if (isset($this->bar)) { // $bar has no default, could be uninitialized - no error
                throw new \Exception('bar is set');
            }
            $this->bar = $bar;
        }
    }

    public function __construct(int $bar)
    {
        $this->bar = $bar;
    }
}
