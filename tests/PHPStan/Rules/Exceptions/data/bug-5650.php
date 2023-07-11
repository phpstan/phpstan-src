<?php

namespace Bug5650;

function (): void {
	try {
		assert(false, new RuntimeException("I am being thrown"));
	} catch (\RuntimeException $e) {

	}
};

function (): void {
	try {
		assert(true, new RuntimeException("I could be but this time am not being thrown"));
	} catch (\RuntimeException $e) {

	}
};

function (): void {
	try {
		assert(false);
	} catch (\RuntimeException $e) {

	}
};

function (): void {
	try {
		assert(true);
	} catch (\RuntimeException $e) {

	}
};
