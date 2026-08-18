<?php declare(strict_types = 1);

if (!function_exists('conditionallyDeclaredFunctionWithoutNamespace')) {
	function conditionallyDeclaredFunctionWithoutNamespace(): void
	{
	}
}

if (!class_exists('ConditionallyDeclaredClassWithoutNamespace')) {
	class ConditionallyDeclaredClassWithoutNamespace
	{
	}
}

if (!defined('GUARDED_DEFINE')) {
	define('GUARDED_DEFINE', 1);
}

const UNCONDITIONAL_CONST = 1;

function unconditionallyDeclaredFunctionWithoutNamespace(): void
{
}
