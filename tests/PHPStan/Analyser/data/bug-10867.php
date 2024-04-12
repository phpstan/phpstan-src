<?php
namespace Bug10867;

function doFoo():void {
	$sysERR = '';
?>

<p><?php  echo constant($sysERR); ?></p>
<?php
}
