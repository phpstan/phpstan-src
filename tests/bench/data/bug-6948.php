<?php declare(strict_types = 1);

/*
 * instructions: 211.9M
 * cycles: 271.1M
 * memory: 1.290MiB
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
