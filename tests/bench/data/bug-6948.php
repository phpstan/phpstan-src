<?php declare(strict_types = 1);

/*
 * instructions: 692.1M
 * cycles: 842.0M
 * memory: 2.177MiB
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
