<?php // lint >= 8.2

namespace SealedSubtypes;

/** @phpstan-sealed _XEnum */
interface _Enum {}
enum _XEnum implements _Enum { case Value; }

/** @phpstan-sealed __XEnumValid | __YEnumInvalid */
interface __EnumError {}
enum __XEnumValid implements __EnumError { case Value; }
enum __YEnumInvalid { case Value; }

/** @phpstan-sealed __XClass | __YClass */
abstract readonly class __Class {}
final readonly class __XClass extends __Class {}
final readonly class __YClass extends __Class {}

/** @phpstan-sealed __XClassValid | __YClassInvalid */
abstract readonly class __ClassError {}
final readonly class __XClassValid extends __ClassError {}
final readonly class __YClassInvalid {}
