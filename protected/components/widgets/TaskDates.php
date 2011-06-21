<?php
/**
 * TaskDates class file.
 *
 * @author Ajay Sharma
 */

/**
 * TaskDates lists the dates of the tasks in a nice way 
 **/
class TaskDates extends CWidget
{
	/**
	 * The task whose data to display
	 * @var Task
	 */
	public $task;
	
	private $text;
	
	public function run() {
		if(!$this->task->hasStarts && !$this->task->hasEnds) {
			echo PHtml::openTag('time', array('class'=>'starts'));
			echo PHtml::link(
				PHtml::encode('Add times'), 
				array('task/update', 'id'=>$this->task->id),
				array()
			);
			echo PHtml::closeTag('time');
		}
		
		if($this->task->hasStarts) {
			echo PHtml::openTag('time', array('class'=>'starts'));
			echo PHtml::link(
				PHtml::encode(Yii::app()->format->formatDateTime(strtotime($this->task->starts))), 
				array('task/update', 'id'=>$this->task->id),
				array()
			);
			echo PHtml::closeTag('time');
		}
		
		if($this->task->hasEnds) {
			
			if($this->task->hasOnlyEnds) {
				$formattedEnds = $this->task->getAttributeLabel('ends');
				$formattedEnds .= ' ' . Yii::app()->format->formatDateTime(strtotime($this->task->ends)); 	
			}
			else {
				echo ' - ';
				if($this->task->startDate == $this->task->endDate) {
					$formattedEnds = Yii::app()->format->formatTime(strtotime($this->task->ends));
				}
				else {
					$formattedEnds = ' ' . Yii::app()->format->formatDateTime(strtotime($this->task->ends));
				}
			}
			
			echo PHtml::openTag('time', array('class'=>'ends'));
			echo PHtml::link(
				PHtml::encode($formattedEnds),	 
				array('task/update', 'id'=>$this->task->id),
				array(
					'title' => PHtml::encode(Yii::app()->format->formatDateTime(strtotime($this->task->ends))),
				)
			);
			echo PHtml::closeTag('time');
		}
	}
}