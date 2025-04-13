<?php // lint >= 8.2

namespace IssetOrCoalesceOnNonNullableInitializedProperty;

class User
{
    private ?string $nullableString;
    private string $maybeUninitializedString;
    private string $string;

    private $untyped;

    public function __construct()
    {
        if (rand(0, 1)) {
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

    public function doFooBar(): void
    {
        if (empty($this->maybeUninitializedString)) {
            echo $this->maybeUninitializedString;
        }
        if (empty($this->nullableString)) {
            echo $this->nullableString;
        }
        if (empty($this->string)) {
            echo $this->string;
        }
        if (empty($this->untyped)) {
            echo $this->untyped;
        }
    }
}

class MoreEmptyCases
{
    private false|string $union;
    private false $false;
    private true $true;
    private bool $bool;

    public function __construct()
    {
        if (rand(0, 1)) {
            $this->union = 'nope';
            $this->bool = true;
        } elseif (rand(10, 20)) {
            $this->union = false;
            $this->bool = false;
        }
        $this->false = false;
        $this->true = true;
    }

    public function doFoo(): void
    {
        if (empty($this->union)) {
        }
        if (empty($this->bool)) {
        }
        if (empty($this->false)) {
        }
        if (empty($this->true)) {
        }
    }
}
