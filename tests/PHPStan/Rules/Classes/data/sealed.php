<?php

namespace Sealed;

/**
 * @phpstan-sealed FooClass|BarClass
 */
class BaseClass {}
class FooClass extends BaseClass {}
class BarClass extends BaseClass {}
class BazClass extends BaseClass {} // this is an error

/**
 * @phpstan-sealed FooClass2|BarClass2
 */
interface BaseInterface {}
class FooClass2 implements BaseInterface {}
class BarClass2 implements BaseInterface {}
class BazClass2 implements BaseInterface {} // this is an error

/**
 * @psalm-inheritors FooInterface|BarInterface
 */
interface BaseInterface2 {}
interface FooInterface extends BaseInterface2 {}
interface BarInterface extends BaseInterface2 {}
interface BazInterface extends BaseInterface2 {} // this is an error

class BarClassChild extends BarClass {}
class BarClass2Child extends BarClass2 {}
interface BarInterfaceChild extends BarInterface {}
