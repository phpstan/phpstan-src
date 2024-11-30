<?php

namespace ClassAsClassConstant;

final class A
{
	public const FOO = 'foo';
	public const BAR = 'bar';
	public const CLASS = 'qux';
}

final class B
{
	public const YES = 1,
		NO = 0,
		CLASS = -1;
}
