<?php // lint >= 7.4

namespace IssetOrCoalesceOnNonNullableInitializedProperty;

use function PHPStan\debugScope;

class User
{
    private ?string $nullableString;
    private string $maybeUninitializedString;
    private string $string;

    private $untyped;

    public function __construct(
    ) {
        if (rand(0,1)) {
            $this->nullableString = 'hello';
            $this->string = 'world';
            $this->maybeUninitializedString = 'something';
        } else {
            $this->nullableString = null;
            $this->string = 'world 2';
            $this->untyped = 123;
        }
    }

    public function doFoo(): void
    {
        if (isset($this->maybeUninitializedString)) {
            echo $this->maybeUninitializedString;
        }
        if (isset($this->nullableString)) {
            echo $this->nullableString;
        }
        if (isset($this->string)) {
            echo $this->string;
        }
        if (isset($this->untyped)) {
            echo $this->untyped;
        }
    }

    public function doBar(): void
    {
        echo $this->maybeUninitializedString ?? 'default';
        echo $this->nullableString ?? 'default';
        echo $this->string ?? 'default';
        echo $this->untyped ?? 'default';
    }
}
