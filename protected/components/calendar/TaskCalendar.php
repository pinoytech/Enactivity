<?php

Yii::import("application.components.calendar.Month");

class TaskCalendar extends CComponent {

	private $dates = array();
	private $someday = array();

	public $month = null;
	
	/**
	 * Construct a new Task Calendar using the tasks 
	 * @param array an array of Task models (allows nesting of arrays)
	 **/
	public function __construct($tasks = array(), $month = null) {
		Yii::beginProfile("TaskCalendar __construct", get_class($this));

		$this->month = $month;
		$this->addTasks($tasks);
		
		Yii::endProfile("TaskCalendar __construct", get_class($this));
	}

	public static function loadCalendarNextTasks(User $user) {
		$nextTasks = $user->incompleteTasks;
		$futureTasks = $user->futureTasks;
		$somedayTasks = $user->somedayNotCompletedTasks;

		$calendar = new TaskCalendar(array(
			$nextTasks, 
			$futureTasks,
			$somedayTasks,
		));

		$ignorableTasks = $user->ignoredOrCompletedTasks;
		$calendar->removeTasks($ignorableTasks);

		$ignorableSomedayTasks = $user->ignoredOrCompletedSomedayTasks;
		$calendar->removeTasks($ignorableSomedayTasks);

		return $calendar;
	}

	public static function loadCalendarByMonth($user, $month) {

		$tasks = User::model()->with(array(
			'tasks'=>array(
				'scopes'=>array(
					'scopeByMonth' => array($month),
				),
			),
		))->findByPk($user->id)->tasks;
		
		return new TaskCalendar($tasks, $month);
	}

	public static function loadCalendarWithNoStart($user) {
		$incompleteSomedayTasks = $user->incompleteSomedayTasks;
		$somedayTasks = $user->somedayNotCompletedTasks;

		$calendar = new TaskCalendar(array(
			$incompleteSomedayTasks,
			$somedayTasks,
		));

		$ignorableSomedayTasks = $user->ignoredOrCompletedSomedayTasks;
		$calendar->removeTasks($ignorableSomedayTasks);
		
		return $calendar;
	}

	/**
	 * Load a list of tasks into a calendar
	 * @param array $tasks list of tasks, allows for nested arrays too
	 */
	public function addTasks($tasks) {
		/** @var $task Task **/
		foreach ($tasks as $task) {
			if(is_array($task)) {
				$this->addTasks($task);
			}
			else {
				$this->addTask($task);
			}
		}
	}

	/**
	 * Removes a list of tasks from the calendar
	 * @param array $tasks list of tasks, allows for nested arrays too
	 **/
	public function removeTasks($tasks) {
		/** @var $task Task **/
		foreach ($tasks as $task) {
			if(is_array($task)) {
				$this->removeTasks($task);
			}
			else {
				$this->removeTask($task);
			}
		}
	}	
	
	/**
	 * Adds a task to the calendar.
	 * @param Task
	 * @return null
	 **/
	public function addTask(Task $task) {
		Yii::beginProfile('TaskCalendar addTask', get_class($this));
		Yii::trace("Adding task {$task->id}", get_class($this));

		if($task->hasStarts) {
			$this->addTaskWithStartTime($task);
		}
		else {
			$this->addTaskWithNoStartTime($task);	
		}
		Yii::endProfile('TaskCalendar addTask', get_class($this));
	}

	protected function addTaskWithStartTime($task) {
		if($task->hasStarts) {
			$date = $task->startDate;
			$time = $task->formattedStartTime;
			$activityId = $task->activityId;

			if(isset($this->dates[$date][$time][$activityId]['activity'])) {
				$this->dates[$date][$time][$activityId]['taskCount']++;
				$this->dates[$date][$time][$activityId]['more']++;
			}
			else {
				$this->dates[$date][$time][$activityId]['activity'] = $task->activity;
				$this->dates[$date][$time][$activityId]['firstTask'] = $task;
				$this->dates[$date][$time][$activityId]['taskCount'] = 1;
				$this->dates[$date][$time][$activityId]['more'] = 0;
			}

			$this->dates[$date][$time][$activityId]['tasks'] = self::addTaskToList($task, $this->dates[$date][$time][$activityId]['tasks']);

		}
		else {
			throw new CException("Attempting to add a task with start time, but task has no start time");
		}
	}

	protected function addTaskWithNoStartTime($task) {
		if(!$task->hasStarts) {

			if(isset($this->someday[$task->activityId]['activity'])) {
				$this->someday[$task->activityId]['taskCount']++;
				$this->someday[$task->activityId]['more']++;
			}
			else {
				$this->someday[$task->activityId]['activity'] = $task->activity;
				$this->someday[$task->activityId]['taskCount'] = 1;
				$this->someday[$task->activityId]['more'] = 0;
			}

			$this->someday[$task->activityId]['tasks'] = self::addTaskToList($task, $this->someday[$task->activityId]['tasks']);

		}
		else {
			throw new CException("Attempting to add a task with no start time, but task has a start time");
		}
	}

	public function removeTask($task) {
		Yii::beginProfile('TaskCalendar removeTask', get_class($this));
		Yii::trace("Removing task {$task->id}", get_class($this));

		if($task->hasStarts) {
			$this->removeTaskWithStartTime($task);
		}
		else {
			$this->removeTaskWithNoStartTime($task);
		}
		Yii::endProfile('TaskCalendar removeTask', get_class($this));
	}

	protected function removeTaskWithStartTime($task) {
		if($task->hasStarts) {
			$date = $task->startDate;
			$time = $task->formattedStartTime;
			$activityId = $task->activityId;

			// FIXME: does not remove
			if(self::getIsTaskInList($task, $this->dates[$date][$time][$activityId]['tasks'])) {

				if(isset($this->dates[$date][$time][$activityId])) {

					$this->dates[$date][$time][$activityId]['tasks'] = self::removeTaskFromList($task, $this->dates[$date][$time][$activityId]['tasks']);

					$this->dates[$date][$time][$activityId]['taskCount']--;
					$this->dates[$date][$time][$activityId]['more']--;

					if(empty($this->dates[$date][$time][$activityId]['tasks'])) {
						unset($this->dates[$date][$time][$activityId]);

						if(empty($this->dates[$task->startDate][$task->formattedStartTime])) {
							unset($this->dates[$task->startDate][$task->formattedStartTime]);
						}

						if(empty($this->dates[$task->startDate])) {
							unset($this->dates[$task->startDate]);
						}
					}
				}
			}
			else {
				Yii::trace("Task {$task->id} not in calendar", get_class($this));	
			}
		}
		else {
			throw new CException("Attempting to remove a task with start time, but task has no start time");
		}
	}

	protected function removeTaskWithNoStartTime($task) {
		if(!$task->hasStarts) {
			// [activityId]['tasks']
			if(self::getIsTaskInList($task, $this->someday[$task->activityId]['tasks'])) { // if record exists in hash

				if(isset($this->someday[$task->activityId]['activity'])) { // drop activity count

					$this->someday[$task->activityId]['tasks'] = self::removeTaskFromList($task, $this->someday[$task->activityId]['tasks']);

					$this->someday[$task->activityId]['taskCount']--;
					$this->someday[$task->activityId]['more']--;

					if($this->someday[$task->activityId]['taskCount'] <= 0) { // if was last task in hash for activity
						unset($this->someday[$task->activityId]);
					}
				}
			}
			else {
				Yii::trace("Task {$task->id} not in calendar", get_class($this));	
			}
		}
		else {
			throw new CException("Attempting to remove a task with no start time, but task has a start time");
		}
	}	
	
	/**
	 * @return array of Tasks in form $date => $time => {array of Tasks}
	 */
	public function getDatedTasks() {
		return $this->dates;
	}
	
	/**
	 * @return array of Tasks
	 */
	public function getSomedayTasks() {
		return $this->someday;
	}
	
	/**
	 * Get all tasks on a given day
	 * @param string $date
	 * @return array of Tasks
	 */
	public function getTasksByDate($date) {
		$date = date(Task::DATE_FORMAT, strtotime($date));
		if(isset($this->dates[$date])) {
			return $this->dates[$date];
		}
		return array();
	}

	/**
	 * Are there any tasks in this calendar?
	 * @return boolean
	 **/
	public function getHasTasks() {
		foreach ($this->dates as $date => $times) {
			foreach ($times as $time => $tasks) {
				if(isset($time[$task])) {
					return true;
				}
			}
		}

		return $this->hasSomedayTasks;
	}
	
	/**
	* Get if has tasks on a given day
	* @param string $date
	* @return array of Tasks
	*/
	public function hasTasksOnDate($date) {
		$date = date(Task::DATE_FORMAT, strtotime($date));
		return isset($this->dates[$date]) && !empty($this->dates[$date]);
	}
	
	/** 
	 * @return boolean true if calendar has tasks with no start date
	 **/
	public function getHasSomedayTasks() {
		return !empty($this->somedayTasks);
	}

	/**
	 * @return int number of tasks that occur in the calendar window 
	**/
	public function getDatedTaskCount() {
		$taskCount = 0;

		foreach ($this->dates as $date => $times) {
			foreach ($times as $time => $tasks) {
				$taskCount += sizeof($tasks);
			}
		}
		return $taskCount;
	}

	/**
	 * @return int number of tasks that have no someday
	**/
	public function getSomedayTasksCount() {
		return sizeof($this->someday);
	}

	/**
	 * @return int number of events in calendar
	 */
	public function getTaskCount() {
		return $this->datedTaskCount + $this->somedayTasksCount;
	}

	/**
	 * @see getTaskCount
	 **/
	public function getItemCount() {
		return $this->taskCount;
	}

	public function getNextMonthUrl() {
		return Yii::app()->createAbsoluteUrl('my/calendar', array(
			'month' => $this->month->monthIndex + 1 > 12 ? 1 : $this->month->monthIndex + 1,
			'year' => $this->month->monthIndex + 1 > 12 ? $this->month->year + 1 : $this->month->year,
		));
	}


	public function getPreviousMonthUrl() {
		return Yii::app()->createAbsoluteUrl('my/calendar', array(
			'month' => $this->month->monthIndex - 1 < 1 ? 12 : $this->month->monthIndex - 1,
			'year' => $this->month->monthIndex - 1 < 1 ? $this->month->year - 1 : $this->month->year,
		));
	}

	/**
	 * Returns the key for task if it is found in the list, null otherwise.
	 * @return int|null
	 */
	private static function getIndexOfTask($task, $list) {

		$list = is_array($list) ? $list : array();

		foreach ($list as $key => $storedTask) {
			if(strcasecmp($storedTask->id, $task->id) == 0) {
				return $key;
			}
		}

		return null;
	}

	/**
	 * @return boolean true if task is in list
	 **/
	private static function getIsTaskInList($task, $list) {
		return is_int(self::getIndexOfTask($task, $list));
	}

	/** 
	 * Adds a task to a list, only if the task is not already in the list
	 * @param task to add
	 * @param array list of tasks, generates new array if none exists
	 * @return array updated list
	 **/
	private static function addTaskToList($task, $list) {

		$index = self::getIndexOfTask($task, $list);
		
		if(is_null($index)) {
			$list[] = $task;
		}
		
		return $list;
	}

	/** 
	 * Removes the first instance of a task from a list of tasks
 	 * @param Task task to remove
	 * @param array list of tasks
	 * @return array updated list
	 */
	private static function removeTaskFromList($task, $list) {
		$index = self::getIndexOfTask($task, $list);
		
		if(isset($index)) {
			unset($list[$index]);
			Yii::trace("Task {$task->id} has been removed", get_class($this));	
		}

		return $list;
	}
}