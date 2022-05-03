<?php

namespace OptimizedDirectory;

class BFoo
{

	const CLASS_CONST = 'class_const';

	function doBar()
	{

	}

}

function doBar()
{

}

function doBaz()
{

}

function &get_smarty()
{
	global $smarty;

	return $smarty;
}

function & get_smarty2()
{
	global $smarty;

	return $smarty;
}

Function upperCaseFunction()
{

}

const SOMETHING = 'something';
