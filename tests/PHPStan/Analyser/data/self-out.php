<?php

namespace SelfOut;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class a {
    /**
     * @var list<T>
     */
    private array $data;
    /**
     * @param T $data
     */
    public function __construct($data) {
        $this->data = [$data];
    }
    /**
     * @template NewT
     *
     * @param NewT $data
     *
     * @phpstan-self-out self<T|NewT>
     *
     * @return void
     */
    public function addData($data) {
        /** @var self<T|NewT> $this */
        $this->data []= $data;
    }
    /**
     * @template NewT
     *
     * @param NewT $data
     *
     * @phpstan-self-out self<NewT>
     *
     * @return void
     */
    public function setData($data) {
        /** @var self<NewT> $this */
        $this->data = [$data];
    }
    /**
     * @return ($this is a<int> ? void : never)
     */
    public function test(): void {
    }
}

function () {
	$i = new a(123);
	// OK - $i is a<123>
	assertType('SelfOut\\a<int>', $i);
	assertType('void', $i->test());

	$i->addData(321);
	// OK - $i is a<123|321>
	assertType('SelfOut\\a<int>', $i);
	assertType('void', $i->test());

	$i->setData("test");
	// IfThisIsMismatch - Class is not a<int> as required
	assertType('SelfOut\\a<string>', $i);
	assertType('*NEVER*', $i->test());
};
