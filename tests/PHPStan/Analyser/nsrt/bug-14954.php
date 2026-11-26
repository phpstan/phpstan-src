<?php declare(strict_types = 1);

namespace Bug14954;

use function PHPStan\Testing\assertType;

$xLooksLikeNonEmptyString = array_key_exists( 'x', $_POST) && is_string($_POST['x']) && trim($_POST['x']) !== '';
$yLooksLikeNonEmptyString = array_key_exists( 'y', $_POST) && is_string($_POST['y']) && trim($_POST['y']) !== '';
$idLooksLikeNonNegativeInt = array_key_exists( 'id', $_POST) && is_string($_POST['id']) && ctype_digit($_POST['id']);

$messages = [];
$process = false;
// If you comment out this if else-if block, phpstan knows $_POST['id'] is a numeric-string
if ( ! $xLooksLikeNonEmptyString )
{
	$messages[] = "Please specify x";	
}
else if ( strlen($_POST['x']) > 20 )
{
	$messages[] = "x is too long";
}
if ( ! $idLooksLikeNonNegativeInt )
{
	$messages[] = "Doesn't look like a non-negative integer";
}
else if ( (int) $_POST['id'] < 1 )
{
	$messages[] = "id is less than 1";	
}	
// If you comment out this if block, or move it before the previous if else-if block, phpstan knows $_POST['id'] is a numeric-string
if ( $yLooksLikeNonEmptyString && strlen($_POST['y']) > 200 )
{
	$messages[] = "y is too long";
}

if ( count($messages) === 0 )
{
  $process = true;	
}

if ( $process )
{
    assertType('numeric-string', $_POST['id']);
	$a = str_repeat('A', (int) $_POST['id'] );
}