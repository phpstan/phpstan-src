<?php // lint >= 8.4

namespace AbstractFinalHookParseError;

abstract class User
{
	final abstract public string $bar {
		get;
	}
}
