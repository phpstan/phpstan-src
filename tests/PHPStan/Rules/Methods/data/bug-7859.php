<?php

namespace Bug7859;

interface SomeInterface
{
	public function getList(mixed $a) :void;
}

// --- Class that implements an interface and its child ---
class ImplementingSomeInterface implements SomeInterface
{
	public function getList(mixed $a, bool $b = false): void
	{
	}
}

class ExtendingClassImplementingSomeInterface extends ImplementingSomeInterface
{
	// does not show error as expected
	public function getList(mixed $a): void
	{
	}
}

// --- Class (without and interface) and its child ---
class NotImplementingSomeInterface
{
	public function getList(mixed $a, bool $b = false): void
	{
	}
}

class ExtendingClassNotImplementingSomeInterface extends NotImplementingSomeInterface
{
	// shows error as expected
	public function getList(mixed $a): void
	{
	}
}
