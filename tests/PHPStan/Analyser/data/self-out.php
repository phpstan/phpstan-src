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

/**
 * @template T
 * @extends a<T>
 */
class b extends a {
	/**
	 * @param T $data
	 */
	public function __construct($data) {
		parent::__construct($data);
	}
}

function () {
	$i = new a(123);
	// OK - $i is a<123>
	assertType('SelfOut\\a<int>', $i);
	assertType('null', $i->test());

	$i->addData(321);
	// OK - $i is a<123|321>
	assertType('SelfOut\\a<int>', $i);
	assertType('null', $i->test());

	$i->setData("test");
	// IfThisIsMismatch - Class is not a<int> as required
	assertType('SelfOut\\a<string>', $i);
	assertType('never', $i->test());
};

function () {
	$i = new b(123);
	assertType('SelfOut\\b<int>', $i);

	$i->addData(321);
	assertType('SelfOut\\a<int>', $i);
};
