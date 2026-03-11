<?php

namespace Bug10147;

abstract class AbstractClass
{
	const BASE_URL = self::BASE_URL;
}

final class TestClass extends AbstractClass
{
	const BASE_URL = 'http://example.com';
}
