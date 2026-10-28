<?php

namespace InstantiationClassExistsInCatch;

/** @throws \RuntimeException */
function className(): string
{
	throw new \RuntimeException();
}

function (): void {
	try {
		class_exists(className());
	} catch (\RuntimeException $e) {
		new \InstantiationClassExistsInCatch\Missing();
	}
};
