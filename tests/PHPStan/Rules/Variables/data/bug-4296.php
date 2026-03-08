<?php declare(strict_types = 1);

namespace Bug4296;

class Test {
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}

$map = [];
foreach ([new Test('1234')] as $test) {
    $map[$test->getId()] = $test;
}

foreach (['1234'] as $value) {
    if (isset($map[$value])) {
        $found = 1;
    }
}
