<?php

namespace FunctionReflectionDocTest;

if (!function_exists('myFunction')) {
	/** some fn phpdoc */
	function myFunction()
	{

	}
}

if (!function_exists('noDocFunction')) {
	function noDocFunction()
	{
	}
}

if (!function_exists('docViaStub')) {
	function docViaStub()
	{
	}
}

if (!function_exists('existingDocButStubOverridden')) {
	function existingDocButStubOverridden()
	{
	}
}
