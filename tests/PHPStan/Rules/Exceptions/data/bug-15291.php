<?php

namespace Bug15291;

function createFromDateStringInvalid(): void {
	try {
		\DateInterval::createFromDateString('foo');
	} catch (\Exception $e) {
	}
}

function createFromDateStringValid(): void {
	try {
		\DateInterval::createFromDateString('1 day');
	} catch (\Exception $e) {
	}
}

function createFromDateStringUnknown(string $s): void {
	try {
		\DateInterval::createFromDateString($s);
	} catch (\Exception $e) {
	}
}

function modifyInvalid(): void {
	try {
		(new \DateTime())->modify('foo');
	} catch (\Exception $e) {
	}
}

function modifyValid(): void {
	try {
		(new \DateTime())->modify('+1 day');
	} catch (\Exception $e) {
	}
}

function modifyImmutableInvalid(): void {
	try {
		(new \DateTimeImmutable())->modify('foo');
	} catch (\Exception $e) {
	}
}

function modifyUnknown(string $s): void {
	try {
		(new \DateTime())->modify($s);
	} catch (\Exception $e) {
	}
}
