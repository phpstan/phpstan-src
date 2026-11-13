<?php

/*
 * instructions: 11.28M
 * cycles: 18.11M
 * memory: 35.80MiB
 */

namespace Bug10147;

abstract class AbstractClass
{
	const BASE_URL = self::BASE_URL;
}

final class TestClass extends AbstractClass
{
	const BASE_URL = 'http://example.com';
}
