<?php declare(strict_types = 1);

$im = imagecreatefromwebp('');
if (false !== $im) {
	imagejpeg($im);
}
