<?php

namespace FunctionDefinitionPhpVersionRange;

function requiredAfterOptional($a = 1, $b): void
{
}

function requiredAfterNullableOptional(?int $a = null, $b): void
{
}

function implicitlyNullable(int $a = null): void
{
}
