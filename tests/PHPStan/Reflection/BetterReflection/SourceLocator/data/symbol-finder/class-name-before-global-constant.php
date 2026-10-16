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
