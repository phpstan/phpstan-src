<?php declare(strict_types = 1);

namespace ForwardStaticCallRule;

class Base
{

	public static function greet(string $name): string
	{
		return 'hi ' . $name;
	}

}

class Caller extends Base
{

	public static function doFoo(): void
	{
		forward_static_call([Base::class, 'greet'], 'John');
		forward_static_call([Base::class, 'greet']);
		forward_static_call([Base::class, 'greet'], 42);
		forward_static_call('ForwardStaticCallRule\Base::greet');
		forward_static_call_array([Base::class, 'greet'], ['John']);
		forward_static_call_array([Base::class, 'greet'], []);
		forward_static_call_array([Base::class, 'greet'], [42]);
	}

}
