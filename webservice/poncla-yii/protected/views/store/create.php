<?php
/**
 * 
 * @uses Cart $model
 */

$this->pageTitle = 'Build a Sweater';
?>

<?php echo PHtml::beginContentHeader(); ?>
	<h1><?php echo PHtml::encode($this->pageTitle);?></h1>
<?php echo PHtml::endContentHeader(); ?>

<div class="novel">
	<section>
		<?php echo $this->renderPartial('/cartItem/_form', array('model'=>$model)); ?>
	</section>
</div>