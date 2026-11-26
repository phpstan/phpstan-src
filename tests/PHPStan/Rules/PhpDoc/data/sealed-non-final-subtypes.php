<?php // lint >= 8.1

namespace SealedNonFinalSubtypes;

/**
 * @phpstan-sealed InvalidX|InvalidY|InvalidZ
 */
interface InvalidSealed {}

final class InvalidX implements InvalidSealed {}
final class InvalidY implements InvalidSealed {}
final class InvalidZ {}

/**
 * @phpstan-sealed ValidX|ValidY|ValidZ
 */
interface ValidSealed {}

final class ValidX implements ValidSealed {}
final class ValidY implements ValidSealed {}
class ValidZ {}
class ValidZZ extends ValidZ {}
