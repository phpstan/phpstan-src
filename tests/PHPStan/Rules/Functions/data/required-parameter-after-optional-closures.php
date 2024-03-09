<?php

namespace RequiredAfterOptional;

function ($foo = null, $bar): void // not OK
{
};

function (int $foo = null, $bar): void // is OK
{
};

function (int $foo = 1, $bar): void // not OK
{
};

function(bool $foo = true, $bar): void // not OK
{
};

function (?int $foo = 1, $bar): void // not OK
{
};

function (?int $foo = null, $bar): void // not OK
{
};
