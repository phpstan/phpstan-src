<?php declare(strict_types = 1);

namespace Bug7531;

/** @var int $compareTo */

?>

<?php foreach ([1,2,3] as $number) : ?>
	<?php if ($number > $compareTo) : ?>
		<xs:sequence>some xml data</xs:sequence>
	<?php else : ?>
		<?php throw new \Exception("Unexpected behavior") ?>
    <?php endif ?>
<?php endforeach; ?>

<?php foreach ([1,2,3] as $number) : ?>
	<?php if ($number > $compareTo) : ?>
		<xs:sequence>some xml data</xs:sequence>
	<?php else : ?>
		<?php throw new \Exception("Unexpected behavior") ?>
		<?php throw new \Exception("Unreachable") ?>
	<?php endif ?>
<?php endforeach; ?>
