<?php

namespace OverridingVariadics;

interface ITranslator
{

	/**
	 * Translates the given string.
	 * @param  mixed  $message
	 * @param  string  ...$parameters
	 */
	function translate($message, string ...$parameters): string;

}

class Translator implements ITranslator
{

	/**
	 * @param string $message
	 * @param string ...$parameters
	 */
	public function translate($message, $lang = 'cs', string ...$parameters): string
	{

	}

}

class OtherTranslator implements ITranslator
{

	public function translate($message, $lang, string ...$parameters): string
	{

	}

}

class AnotherTranslator implements ITranslator
{

	public function translate($message, $lang = 'cs', string $parameters): string
	{

	}

}

class YetAnotherTranslator implements ITranslator
{

	public function translate($message, $lang = 'cs'): string
	{

	}

}

class ReflectionClass extends \ReflectionClass
{

	public function newInstance($arg = null, ...$args)
	{

	}

}
