<?php
$this->pageTitle = Yii::app()->format->formatMonth($month->timestamp) . " " . $month->year;
// nav
$calendarMenu = array();
$calendarMenu[] = array(
	'label'=>'Previous', 
	'url'=>array('task/calendar',
		'month' => $month->intValue - 1 < 1 ? 12 : $month->intValue - 1,
		'year' => $month->intValue - 1 < 1 ? $month->year - 1 : $month->year,
),
	'linkOptions'=>array(
		'id'=>'task-previous-month-menu-item',
		'class'=>'task-previous-month-menu-item',
	),
);

$calendarMenu[] = array(
	'label'=>'Next', 
	'url'=>array('task/calendar',
		'month' => $month->intValue + 1 > 12 ? 1 : $month->intValue + 1,
		'year' => $month->intValue + 1 > 12 ? $month->year + 1 : $month->year,
),
	'linkOptions'=>array(
		'id'=>'task-next-month-menu-item',
		'class'=>'task-next-month-menu-item',
	),
);
?>

<?php echo PHtml::beginContentHeader(); ?>
	<nav class="calendar-nav">
	<?php $this->widget('zii.widgets.CMenu', array(
		'items'=>$calendarMenu,
	)); ?>
	</nav>
	<h1><?php echo PHtml::encode($this->pageTitle); ?></h1>
<?php echo PHtml::endContentHeader(); ?>

<div class="novel">
	<section id="calendar" class="calendar">
	<?php 
	// show task calendar
	echo $this->renderPartial('_calendar', array(
		'dataProvider'=>$datedTasksProvider,
		'month'=>$month,
	));
	?>
	</section>
</div>

<div class="novel">
	<section id="agenda" class="agenda">
		<?php
		// agenda
		echo $this->renderPartial('_agenda', array(
			'datedTasks'=>$datedTasksProvider->data,
			'datelessTasks'=>$datelessTasksProvider->data,
			'showParent'=>'true',
		));

		echo $this->renderPartial('_form', array('model'=>$newTask, 'inline'=>true)); ?>
	</section>
</div>