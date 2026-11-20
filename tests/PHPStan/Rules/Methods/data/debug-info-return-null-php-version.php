<?php

namespace DebugInfoReturnNullPhpVersion;

if (PHP_VERSION_ID < 80500) {
	class Before
	{

		public function __debugInfo()
		{
			return null;
		}

	}
}

if (PHP_VERSION_ID >= 80500) {
	class After
	{

		public function __debugInfo()
		{
			return null;
		}

	}
}
