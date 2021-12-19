<?php

namespace Bug5446;

/** @template T */
class A {}
/** @template T */
class B {}
/** @template T */
class C {}
/**
 * @template S
 * @template T
 */
class D {}

namespace barBug5446;

/**
 * @template First of \Bug5446\A
 * @template Second of \Bug5446\B
 * @template Third of \Bug5446\C<Second>
 * @template Fourth of \Bug5446\D<First, Third>
 */
class X {}
