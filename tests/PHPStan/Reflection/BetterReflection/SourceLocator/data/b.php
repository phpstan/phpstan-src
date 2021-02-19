<?php

class SingleFileSourceLocatorTestClass
{

}

function singleFileSourceLocatorTestFunction()
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
