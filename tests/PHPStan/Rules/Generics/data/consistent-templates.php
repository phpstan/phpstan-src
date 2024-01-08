<?php

namespace ConsistentTemplates;

/**
 * @template T
 * @phpstan-consistent-templates
 */
class ConsistentFoo {}

/**
 * @template T
 * @template K
 * @extends ConsistentFoo<T>
 */
class ClassWithTwoTemplates extends ConsistentFoo {}

/**
 * @template T
 * @extends ConsistentFoo<int>
 */
class ClassExtendingWithNonTemplateType extends ConsistentFoo {}

/**
 * @template T
 * @phpstan-consistent-templates
 */
interface ConsistentBar {}

/**
 * @template T
 * @template K
 * @implements ConsistentBar<T>
 */
class ClassWithTwoTemplatesI implements ConsistentBar {}

/**
 * @template T
 * @implements ConsistentBar<int>
 */
class ClassExtendingWithNonTemplateTypeI implements ConsistentBar {}

/**
 * @extends ConsistentFoo<int>
 * @implements ConsistentBar<int>
 */
class Baz extends ConsistentFoo implements ConsistentBar {}

/** @extends ClassWithTwoTemplates<int, string> */
class NoErrorForGrandparent extends ClassWithTwoTemplates {}
