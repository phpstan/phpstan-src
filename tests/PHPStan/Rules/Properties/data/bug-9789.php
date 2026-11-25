<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug9789;

trait T {
	public function __construct(public readonly string $value) {}
}

class C {

    use T {
        __construct as protected init;
    }

    public function __construct(string $value) {
        $this->init($value);
        if (!$this->isValid()) {
            throw new \Exception();
        }
    }

	private function isValid(): bool {
		return !empty($this->value);
	}
}
