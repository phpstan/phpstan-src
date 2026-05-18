<?php

function testIssetOnGenericObject(object $obj): void
{
	if (isset($obj->foo)) {
	}
}

function testChainedIssetOnGenericObject(object $obj): void
{
	if (isset($obj->foo) && isset($obj->bar)) {
	}
}

function testIssetAfterIsObjectNarrowing(string $date): void
{
	if (is_object($obj = json_decode($date))) {
		if (isset($obj->crashId) && is_string($obj->crashId)) {
			var_dump($obj->crashId);
		}
	}
}
