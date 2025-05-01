<?php // lint >= 8.4

declare(strict_types = 0);

namespace Bug12393bPhp84;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class StringableFoo {
    private string $foo;

    // https://3v4l.org/nelJF#v8.4.6
    public function doFoo3(\BcMath\Number $foo): void {
        $this->foo = $foo;
        assertType('non-empty-string&numeric-string', $this->foo);
    }

    public function __toString(): string {
        return 'Foo';
    }
}
