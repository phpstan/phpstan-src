<?php // lint >= 8.1

namespace Bug9005;

enum Test: string
{
	private const PREFIX = 'my-stuff-';

	case TESTING = self::PREFIX . 'test';

	case TESTING2 = self::PREFIX . 'test2';
}
