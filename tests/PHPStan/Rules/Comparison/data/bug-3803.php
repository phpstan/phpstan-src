<?php declare(strict_types = 1);

namespace Bug3803;

/** @param array<string> $chars */
function fun(array $chars) : void{
	$string = "";
	foreach($chars as $k => $v){
		$string[$k] = $v;
	}
	if($string === "wheee"){
		var_dump("yes");
	}
}

fun(["w", "h", "e", "e", "e"]);
