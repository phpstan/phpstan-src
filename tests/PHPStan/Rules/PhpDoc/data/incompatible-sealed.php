<?php // lint >= 8.1

namespace IncompatibleSealed;

class SomeClass {};
interface SomeInterface {};

/**
 * @phpstan-sealed SomeClass
 */
trait InvalidTrait1 {}

/**
 * @phpstan-sealed SomeClass
 */
enum InvalidEnum {}

/**
 * @phpstan-sealed UnknownClass
 */
class InvalidClass {}

/**
 * @phpstan-sealed UnknownClass
 */
interface InvalidInterface {}

/**
 * @phpstan-sealed SomeClass
 */
class Valid {}

/**
 * @phpstan-sealed SomeClass
 */
interface ValidInterface {}

/**
 * @phpstan-sealed SomeInterface
 */
interface ValidInterface2 {}

/**
 * @phpstan-sealed UnknownClass|SomeClass
 */
class InvalidClassWithUnion {}
