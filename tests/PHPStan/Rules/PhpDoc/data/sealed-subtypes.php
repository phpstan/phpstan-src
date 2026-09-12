<?php // lint >= 8.2

namespace SealedSubtypes;

/**
 * Subtyping is reflexive, so Reflexivity is a subtype of itself.
 * This is a degenerate sealed declaration, but it is valid and must not report an error.
 *
 * @phpstan-sealed Reflexivity
 */
interface Reflexivity {}

/** @phpstan-sealed _XEnum */
interface _Enum {}
enum _XEnum implements _Enum { case Value; }

/** @phpstan-sealed __XEnum | __YEnum */
interface __Enum {}
enum __XEnum implements __Enum { case Value; }
enum __YEnum implements __Enum { case Value; }

/** @phpstan-sealed __XEnumValid | __YEnumInvalid */
interface __EnumError {}
enum __XEnumValid implements __EnumError { case Value; }
enum __YEnumInvalid { case Value; }

/** @phpstan-sealed _XInterface */
interface _Interface {}
interface _XInterface extends _Interface {}

/** @phpstan-sealed __XInterface | __YInterface */
interface __Interface {}
interface __XInterface extends __Interface {}
interface __YInterface extends __Interface {}

/** @phpstan-sealed __XInterfaceValid | __YInterfaceInvalid */
interface __InterfaceError {}
interface __XInterfaceValid extends __InterfaceError {}
interface __YInterfaceInvalid {}

/** @phpstan-sealed _XAbstractClass */
abstract readonly class _AbstractClass {}
abstract readonly class _XAbstractClass extends _AbstractClass {}

/** @phpstan-sealed __XAbstractClass | __YAbstractClass */
abstract readonly class __AbstractClass {}
abstract readonly class __XAbstractClass extends __AbstractClass {}
abstract readonly class __YAbstractClass extends __AbstractClass {}

/** @phpstan-sealed __XAbstractClassValid | __YAbstractClassInvalid */
abstract readonly class __AbstractClassError {}
abstract readonly class __XAbstractClassValid extends __AbstractClassError {}
abstract readonly class __YAbstractClassInvalid {}

/** @phpstan-sealed _XClass */
abstract readonly class _Class {}
final readonly class _XClass extends _Class {}

/** @phpstan-sealed __XClass | __YClass */
abstract readonly class __Class {}
final readonly class __XClass extends __Class {}
final readonly class __YClass extends __Class {}

/** @phpstan-sealed __XClassValid | __YClassInvalid */
abstract readonly class __ClassError {}
final readonly class __XClassValid extends __ClassError {}
final readonly class __YClassInvalid {}
