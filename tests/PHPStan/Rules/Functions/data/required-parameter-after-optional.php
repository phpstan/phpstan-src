<?php // lint >= 8.0

namespace RequiredAfterOptional;

function doFoo($foo = null, $bar): void // not OK
{

}

function doBar(int $foo = null, $bar): void // is OK
{
}

function doBaz(int $foo = 1, $bar): void // not OK
{
}

function doLorem(bool $foo = true, $bar): void // not OK
{
}

function doIpsum(bool $foo = true, ...$bar): void // OK
{
}

function doDolor(?int $foo = 1, $bar): void // not OK
{
}

function doSit(?int $foo = null, $bar): void // not OK
{
}

function doAmet(int|null $foo = 1, $bar): void // not OK
{
}

function doConsectetur(int|null $foo = null, $bar): void // not OK
{
}

function doAdipiscing(mixed $foo = 1, $bar): void // not OK
{
}

function doElit(mixed $foo = null, $bar): void // not OK
{
}
