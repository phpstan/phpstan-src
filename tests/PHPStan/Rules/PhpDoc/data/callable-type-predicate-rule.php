<?php

namespace CallableTypePredicateRule;

/**
 * @param callable(mixed $value): ($value is int ? true : false) $predicate
 */
function predicateReferencingCallableParameter(callable $predicate): void
{

}

/**
 * @param callable(mixed $value): ($valeu is int ? true : false) $predicate
 */
function predicateWithTypo(callable $predicate): void
{

}

/**
 * @param callable(): ($x is int ? true : false) $cb
 */
function conditionalReferencingFunctionParameter(int $x, callable $cb): void
{

}

/**
 * @param callable(mixed $value): ($value is int ? 'yes' : 'no') $cb
 */
function conditionalWithNonBoolBranches(callable $cb): void
{

}

/**
 * @param callable(mixed $value): ($value is int ? true : false) $predicate
 */
function predicateShadowingFunctionParameter(int $value, callable $predicate): void
{

}
