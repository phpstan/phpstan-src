<?php

namespace PHPStan;

dumpPhpDocType(['' => '']);
dumpPhpDocType(["\0" => 'NUL', 'NUL' => "\0"]);

// Space
dumpPhpDocType([" " => 'SP', 'SP' => ' ']);
dumpPhpDocType(["foo " => 'ends with SP', " foo" => 'starts with SP', " foo " => 'surrounded by SP', 'foo' => 'no SP']);

// Punctuation marks
dumpPhpDocType(["foo?" => 'foo?']);
dumpPhpDocType(["shallwedance" => 'yes']);
dumpPhpDocType(["shallwedance?" => 'yes']);
dumpPhpDocType(["Shall we dance" => 'yes']);
dumpPhpDocType(["Shall we dance?" => 'yes']);
dumpPhpDocType(["shall_we_dance" => 'yes']);
dumpPhpDocType(["shall_we_dance?" => 'yes']);
dumpPhpDocType(["shall-we-dance" => 'yes']);
dumpPhpDocType(["shall-we-dance?" => 'yes']);
dumpPhpDocType(['Let\'s go' => "Let's go"]);
dumpPhpDocType(['Foo\\Bar' => 'Foo\\Bar']);

/**
 * @template T
 * @param T $value
 * @return T
 */
function id(mixed $value): mixed
{
	dumpPhpDocType($value);

	return $value;
}
