<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14999Types;

use function PHPStan\Testing\assertType;

class Message
{

	public static function success(string $string): self
	{
		return new self();
	}

	public function getDisplay(): string
	{
		return '';
	}

}

function doFoo(): void
{
	$message = Message::success('Import has been successfully finished, 2 queries executed. (file.sql)');
	$_SESSION['Import_message'] = [];
	$_SESSION['Import_message']['message'] = $message->getDisplay();
	assertType("non-empty-array&hasOffsetValue('message', string)", $_SESSION['Import_message']);
	$_SESSION['Import_message']['go_back_url'] = 'https://example.com/index.php?route=/server/import';
	assertType("non-empty-array&hasOffsetValue('go_back_url', 'https://example.com/index.php?route=/server/import')&hasOffsetValue('message', string)", $_SESSION['Import_message']);
}

function deeperNesting(Message $message): void
{
	$_SESSION['a']['b']['c'] = $message->getDisplay();
	assertType("non-empty-array&hasOffsetValue('c', string)", $_SESSION['a']['b']);
	assertType("non-empty-array&hasOffsetValue('b', non-empty-array&hasOffsetValue('c', string))", $_SESSION['a']);
}

function otherSuperglobal(Message $message): void
{
	$_GET['a']['b'] = $message->getDisplay();
	assertType("non-empty-array&hasOffsetValue('b', string)", $_GET['a']);
}

function unknownContainer(mixed $m): void
{
	$m['a']['b'] = 1;
	assertType('mixed', $m);
	assertType("non-empty-array&hasOffsetValue('b', 1)", $m['a']);
}

class StaticHolder
{

	/** @var mixed */
	public static $data;

}

function otherWriteForms(Message $message, mixed $appended, mixed $concatenated, mixed $listed): void
{
	$appended['a'][] = $message->getDisplay();
	assertType('non-empty-array', $appended['a']);

	$concatenated['a']['b'] = $message->getDisplay();
	$concatenated['a']['b'] .= 'foo';
	assertType("non-empty-array&hasOffsetValue('b', non-falsy-string)", $concatenated['a']);

	[$listed['a']['b'], $listed['a']['c']] = ['foo', 'bar'];
	assertType("non-empty-array&hasOffsetValue('b', 'foo')&hasOffsetValue('c', 'bar')", $listed['a']);

	StaticHolder::$data['a']['b'] = $message->getDisplay();
	assertType('mixed', StaticHolder::$data);
	assertType("non-empty-array&hasOffsetValue('b', string)", StaticHolder::$data['a']);

	foreach ([1, 2] as $_SESSION['iterated']['value']) {
	}
	assertType("non-empty-array&hasOffsetValue('value', 1|2)", $_SESSION['iterated']);
}
