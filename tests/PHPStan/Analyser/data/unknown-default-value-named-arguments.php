<?php // lint >= 8.1

namespace UnknownDefaultValueNamedArguments;

function doFoo(callable $open, callable $read): void
{
	mt_rand(max: 5);
	(mt_rand(...))(max: 5);
	(session_set_save_handler(...))($open, read: $read);
}
