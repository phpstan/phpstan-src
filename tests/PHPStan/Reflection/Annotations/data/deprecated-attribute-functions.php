<?php

namespace DeprecatedAttributeFunctions;

use Deprecated;

function notDeprecated()
{

}

#[Deprecated]
function foo()
{

}

#[Deprecated('msg')]
function fooWithMessage()
{

}

#[Deprecated(since: '1.0', message: 'msg2')]
function fooWithMessage2()
{

}

#[Deprecated(message: __FUNCTION__)]
function fooWithConstantMessage()
{

}
