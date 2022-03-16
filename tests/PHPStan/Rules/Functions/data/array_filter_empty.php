<?php

/** @var \stdClass[] $objects */
$objects = [];
/** @var array<\stdClass|null> $objectsOrNull */
$objectsOrNull = [];
/** @var array<false|null> $falsey */
$falsey = [];

array_filter([0,1,3]);
array_filter([1,3]);
array_filter(['test']);
array_filter(['', 'test']);
array_filter([null, 'test']);
array_filter([false, 'test']);
array_filter([true, false]);
array_filter([true, true]);
array_filter([new \stdClass()]);
array_filter([new \stdClass(), null]);
array_filter($objects);
array_filter($objectsOrNull);

array_filter([0]);
array_filter([null]);
array_filter([null, null]);
array_filter([null, 0]);
array_filter($falsey);
array_filter([]);
