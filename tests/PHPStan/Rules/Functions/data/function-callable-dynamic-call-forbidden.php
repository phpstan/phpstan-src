<?php // lint >= 8.6

namespace FunctionCallableDynamicCallForbidden;

function doFoo(): void
{
	compact(...);
	extract(...);
	func_get_arg(...);
	func_get_args(...);
	func_num_args(...);
	get_defined_vars(...);
	\COMPACT(...);
	compact('a', ?);
	extract(?);
	array_keys(...);
	get_object_vars(...);
}
