<?php

namespace Bug2634;

function hi(&$thing){
	$thing = "hi";
}

function (): void {
	$array = [];

	hi($array["hi"]);
};
