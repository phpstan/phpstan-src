<?php declare(strict_types = 1);

function symbolFinderClassName(): string
{
	return \stdClass::class;
}

const AFTER_CLASS_NAME = 'found';

function className(): string
{
	return 'not a class declaration';
}

const AFTER_CLASS_PREFIX = 'still found';

function symbolFinderAnonymousClass(): object
{
	return new class {
		public const MEMBER = 'not global';
	};
}

const AFTER_ANONYMOUS_CLASS = 'also found';

function symbolFinderNamedArgument(string $class): string
{
	return $class;
}

function symbolFinderNamedArgumentCall(): string
{
	return symbolFinderNamedArgument(class: 'named argument');
}

const AFTER_NAMED_CLASS_ARGUMENT = 'found after named argument';

function symbolFinderSpacedClassConstant(): string
{
	return \stdClass:: class;
}

const AFTER_SPACED_CLASS_CONSTANT = 'found after spaced class constant';
