<?php declare(strict_types=1);

namespace Bug11441;

use function PHPStan\Testing\assertType;

class Foo {
    public function getParam(): ?string {
        return 'foo';
    }

    /** @phpstan-assert !null $this->getParam() */
    public function checkNotNull(): void {
        if ($this->getParam() === null) {
            throw new \Exception();
        }
    }
}

class Bar {
    public function getParam(): ?int {
        return 1;
    }

    /** @phpstan-assert !null $this->getParam() */
    public function checkNotNull(): void {
        if ($this->getParam() === null) {
            throw new \Exception();
        }
    }
}

function test(Foo|Bar $obj): void {
    assertType('int|string|null', $obj->getParam());

    $obj->checkNotNull();

    assertType('int|string', $obj->getParam());
}
