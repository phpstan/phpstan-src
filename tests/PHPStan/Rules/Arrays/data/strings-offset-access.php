<?php

/** @var int|object $maybeInt */
$maybeInt = null;

$string = 'foo';
$string[0];

$string = 'foo';
$string['foo'];

$string = 'foo';
$string[12.34];

$string = 'foo';
$string[$maybeInt];

/** @var string|array $maybeString */
$maybeString = [];
$maybeString[0];

/** @var string|array $maybeString */
$maybeString = [];
$maybeString['foo'];

/** @var string|array $maybeString */
$maybeString = [];
$maybeString[12.34];

/** @var string|array $maybeString */
$maybeString = [];
$maybeString[$maybeInt];

// Implicit mixed
$mixed = doFoo();
$mixed[0];

// Implicit mixed
$mixed = doFoo();
$mixed['foo'];

// Implicit mixed
$mixed = doFoo();
$mixed[12.34];

// Implicit mixed
$mixed = doFoo();
$mixed[$maybeInt];
