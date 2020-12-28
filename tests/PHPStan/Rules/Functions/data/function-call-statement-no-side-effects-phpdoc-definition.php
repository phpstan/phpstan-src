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
