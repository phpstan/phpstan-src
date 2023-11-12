<?php

namespace Bug9014;

interface FooInterface{
	public function test(string $hurr): void;
}

class Foo implements FooInterface
{
	public function test(string $hurr,string $test = ''): void {}
}

class Bar extends Foo
{
	public function test(string $hurr): void {}
}

abstract class base {
	/**
	 * {@inheritdoc}
	 */
	abstract public function renderForUser();

}

class middle extends base {
	/**
	 * {@inheritdoc}
	 */
	public function renderForUser(): string
	{
	}

}

class extended extends middle
{
	/**
	 * {@inheritdoc}
	 */
	public function renderForUser()
	{
	}
}
