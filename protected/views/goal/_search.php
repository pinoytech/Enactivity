<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'groupId'); ?>
		<?php echo $form->textField($model,'groupId'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'ownerId'); ?>
		<?php echo $form->textField($model,'ownerId'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'isCompleted'); ?>
		<?php echo $form->textField($model,'isCompleted'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'isTrash'); ?>
		<?php echo $form->textField($model,'isTrash'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'created'); ?>
		<?php echo $form->textField($model,'created'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'modified'); ?>
		<?php echo $form->textField($model,'modified'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->