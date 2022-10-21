<?php

namespace ParamOutPhpDocRule;

/**
 * @param-out positive-int $i
 */
function validParamOut(int &$i) {

}

/**
 * @param-out positive-int $i
 * @param-out non-empty-string $s
 */
function multipleValidParamOut(int &$i, string &$s) {

}

/**
 * @param-out int $z
 */
function invalidParamOutParamName(int &$i) {

}

/**
 * @param-out string $i
 */
function differentParamOutType(int &$i) {

}

/**
 * @param-out string $i
 */
function invalidParamOutOnNonReferenceParam(int $i) {

}

/**
 * @param-out array<float, int> $i
 */
function unresolvableParamOutType(int &$i) {

}

/**
 * @param-out \Exception<int, float> $i
 */
function invalidParamOutGeneric(int &$i) {

}

/**
 * @param-out FooBar<mixed> $i
 */
function invalidParamOutWrongGenericParams(int &$i) {

}

/**
 * @param-out FooBar<int> $i
 */
function invalidParamOutNotAllGenericParams(int &$i) {

}

/**
 * @template T of int
 * @template TT of string
 */
class FooBar {
	/**
	 * @param-out T $s
	 */
	function genericClassFoo(mixed &$s): void
	{
	}

	/**
	 * @template S of self
	 * @param-out S $s
	 */
	function genericSelf(mixed &$s): void
	{
	}

	/**
	 * @template S of static
	 * @param-out S $s
	 */
	function genericStatic(mixed &$s): void
	{
	}
}

class C {
	/**
	 * @var \Closure|null
	 */
	private $onCancel;

	public function __construct() {
		$this->foo($this->onCancel);
	}

	/**
	 * @param mixed $onCancel
	 * @param-out \Closure $onCancel
	 */
	public function foo(&$onCancel) : void {
		$onCancel = function (): void {};
	}
}
