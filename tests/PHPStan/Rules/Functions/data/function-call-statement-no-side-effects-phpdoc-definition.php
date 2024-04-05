<?php

namespace FunctionCallStatementNoSideEffectsPhpDoc;

function regular(string $a): string {return $a;}

/**
 * @phpstan-pure
 */
function pure1(string $a): string {return $a;}

/**
 * @psalm-pure
 */
function pure2(string $a): string {return $a;}

/**
 * @pure
 */
function pure3(string $a): string {return $a;}

/**
 * @phan-pure
 */
function pure4(string $a): string {return $a;}

/**
 * @phan-side-effect-free
 */
function pure5(string $a): string {return $a;}

/**
 * @phpstan-pure
 * @throws void
 * @return string
 */
function pureAndThrowsVoid(): string { return 'aaa'; }

/**
 * @phpstan-pure
 * @throws \Exception
 * @return string
 */
function pureAndThrowsException(): string { return 'aaa'; }
