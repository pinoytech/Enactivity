<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'task-form',
	'enableAjaxValidation'=>false,
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'goalId'); ?>
		<?php echo $form->textField($model,'goalId'); ?>
		<?php echo $form->error($model,'goalId'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'name'); ?>
		<?php echo $form->textField($model,'name',array('size'=>60,'maxlength'=>255)); ?>
		<?php echo $form->error($model,'name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'ownerId'); ?>
		<?php echo $form->textField($model,'ownerId'); ?>
		<?php echo $form->error($model,'ownerId'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'priority'); ?>
		<?php echo $form->textField($model,'priority'); ?>
		<?php echo $form->error($model,'priority'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'isCompleted'); ?>
		<?php echo $form->textField($model,'isCompleted'); ?>
		<?php echo $form->error($model,'isCompleted'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'isTrash'); ?>
		<?php echo $form->textField($model,'isTrash'); ?>
		<?php echo $form->error($model,'isTrash'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'starts'); ?>
		<?php echo $form->textField($model,'starts'); ?>
		<?php echo $form->error($model,'starts'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'ends'); ?>
		<?php echo $form->textField($model,'ends'); ?>
		<?php echo $form->error($model,'ends'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'created'); ?>
		<?php echo $form->textField($model,'created'); ?>
		<?php echo $form->error($model,'created'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'modified'); ?>
		<?php echo $form->textField($model,'modified'); ?>
		<?php echo $form->error($model,'modified'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->