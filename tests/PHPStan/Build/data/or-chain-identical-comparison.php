<?php

if ($var === 'foo') {
	echo 'ok';
}

if ($var === 'foo' || $var === 'bar') {
	echo 'ok';
}

if ($var === 'foo' || $var === 'bar' || $var === 'buz') {
	echo 'ok';
}

if ($var === 'foo' || $var === 'bar' || $var === 'buz') {
	echo 'ok';
} elseif ($var === 'foofoo' || $var === 'barbar' || $var === 'buzbuz') {
	echo 'ok';
}

if ($var === 'foo' || $var === 'bar' || $var2 === 'buz') {
	echo 'no';
}

if ($var === 'foo' || $var2 === 'bar' || $var === 'buz') {
	echo 'no';
}

if ($var === 'foo' || $var2 === 'bar' || $var2 === 'buz') {
	echo 'no';
}
