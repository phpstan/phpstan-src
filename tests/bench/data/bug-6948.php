<?php declare(strict_types = 1);

/*
 * instructions: 222.5M
 * cycles: 283.0M
 * memory: 37.03MiB
 */

namespace Bug6948;

$row = [1];
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}

if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}

if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}

if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}

if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}

if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
if (rand()) {
	$row[] = $row;
}
