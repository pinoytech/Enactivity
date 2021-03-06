<?php

Yii::import("application.components.db.ar.ActiveRecord");
Yii::import("application.components.db.ar.EmailableRecord");
Yii::import("application.components.db.ar.LoggableRecord");

Yii::import("ext.facebook.components.db.ar.FacebookGroupPostableRecord");

/**
 * This is the model class for table "task".
 * A task is a single item within a {@link Group} that {@link User}s can sign up for.
 *
 * The following are behaviors used by Task
 * @uses CTimestampBehavior
 * @uses NestedSetBehavior
 * @uses DefaultGroupBehavior
 * @uses DateTimeZoneBehavior
 * @uses ActiveRecordLogBehavior
 * @uses EmailNotificationBehavior
 *
 * The followings are the available columns in table 'task':
 * @property integer $id
 * @property integer $groupId
 * @property integer $taskId
 * @property string $name
 * @property integer $isTrash
 * @property string $starts
 * @property int $participantsCount
 * @property int $participantsCompletedCount
 * @property string $created
 * @property string $modified
 *
 * The followings are the available model relations:
 * @property Task $root
 * @property Group $group
 * @property response[] $responses all response objects related to this Task
 * @property integer $responsesCount number of users who have signed up for the task 
 * @property response[] $participatingresponses active response objects related to the model
 * @property User[] $participants users who are signed up for the Task
 * @property ActiveRecordLog[] $feed
 */
class Task extends ActiveRecord implements EmailableRecord, LoggableRecord, FacebookGroupPostableRecord
{
	const DATE_FORMAT = 'Y-m-d';
	const NAME_MAX_LENGTH = 255;
	const PARTICIPANT_SUMMARY_SIZE = 10;
	
	const SCENARIO_DELETE = 'delete';
	const SCENARIO_INSERT = 'insert'; // default set by Yii
	const SCENARIO_DRAFT = 'draft';
	const SCENARIO_PUBLISH = 'publish';
	const SCENARIO_TRASH = 'trash';
	const SCENARIO_UNTRASH = 'untrash';
	const SCENARIO_UPDATE = 'update'; // default set by Yii

	private $_startDate;
	private $_startTime;

	public $publicAttributes = array(
		'name',
		'starts',
	);

	/**
	 * @var Response the current user's response
	 **/
	private $_myResponse = null;
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return Task the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'task';
	}
	
	/**
	 * @return array behaviors that this model should behave as
	 */
	public function behaviors() {
		return array(
			// Update created and modified dates on before save events
			'CTimestampBehavior'=>array(
				'class' => 'zii.behaviors.CTimestampBehavior',
				'createAttribute' => 'created',
				'updateAttribute' => 'modified',
				'setUpdateOnCreate' => true,
			),
			'DateTimeZoneBehavior'=>array(
				'class' => 'ext.behaviors.DateTimeZoneBehavior',
			),
			// Record C-UD operations to this record
			'ActiveRecordLogBehavior'=>array(
				'class' => 'ext.behaviors.ActiveRecordLogBehavior',
				'scenarios' => array(
					// self::SCENARIO_INSERT => array(),
					// self::SCENARIO_TRASH => array(),
					self::SCENARIO_UPDATE => array(
						'name',
						'starts',
					),
					// self::SCENARIO_UNTRASH => array(),
				),
			),
			// 'EmailNotificationBehavior'=>array(
			// 	'class' => 'ext.behaviors.model.EmailNotificationBehavior',
			// 	'scenarios' => array(
			// 		self::SCENARIO_INSERT => array(),
			// 		self::SCENARIO_TRASH => array(),
			// 		self::SCENARIO_UPDATE => array(
			// 			'name',
			// 			'starts',
			// 		),
			// 		self::SCENARIO_UNTRASH => array(),
			// 	),
			// ),
		);
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs via forms.
		return array(
			array('name, isTrash',
				'required'),
			
			// boolean ints can be 0 or 1
			array('isTrash',
				'numerical',
				'min' => 0,
				'max' => 1,
				'integerOnly'=>true),
			
			// boolean ints defaults to 0
			array('isTrash',
				'default',
				'value' => 0),
			
			array('name',
				'length', 
				'max'=>self::NAME_MAX_LENGTH),
			
			array('name', 
				'filter', 
				'filter'=>'trim'),

			array('starts',
				'application.components.validators.DateTimeValidator',
				'allowEmpty' => true
			),

			array('startDate',
				'application.components.validators.BothOrNeitherValidator',
				'otherAttribute' => 'startTime'
			),

			array('startTime',
				'application.components.validators.BothOrNeitherValidator',
				'otherAttribute' => 'startDate'
			),
			
			array('starts, startDate, startTime',
				'safe'),
			
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			//array('id, groupId, name, isTrash, starts, created, modified',
			//	'safe',
			//	'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// stupid hacky way of escaping statuses
		$participatingWhereIn = '\'' . implode('\', \'', Response::getParticipatingStatuses()) . '\'';

		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'group' => array(self::BELONGS_TO, 'Group', 'groupId'),
			'activity' => array(self::BELONGS_TO, 'Activity', 'activityId'),
			
			'responses' => array(self::HAS_MANY, 'response', 'taskId'),
			'responsesCount' => array(self::STAT, 'response', 'taskId'),
			
			'participatingresponses' => array(self::HAS_MANY, 'response', 'taskId',
				'condition' => 'participatingresponses.status IN (' . $participatingWhereIn . ')',
			),
			'participants' => array(self::HAS_MANY, 'User', 'userId',
				'condition' => 'participatingresponses.status IN (' . $participatingWhereIn . ')',
				'through' => 'participatingresponses',
			),
			
			'feed' => array(self::HAS_MANY, 'ActiveRecordLog', 'focalModelId',
				'condition' => 'feed.focalModel=\'Task\'',
				'order' => 'feed.created DESC',
			),

			'comments' => array(self::HAS_MANY, 'Comment', 'modelId',
				'condition' => 'comments.model=\'Task\'',
				'order' => 'comments.created ASC',
			),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'Id',
			'groupId' => 'Group',
			'name' => 'Name',
			'isTrash' => 'Is Trash',
			'starts' => 'Starts at',
			'created' => 'Created',
			'modified' => 'Modified',
		);
	}
	
	public function scenarioLabels() {
		return array(
			self::SCENARIO_DELETE => 'deleted',
			self::SCENARIO_INSERT => 'created', // default set by Yii
			self::SCENARIO_TRASH => 'trashed',
			self::SCENARIO_UNTRASH => 'untrashed',
			self::SCENARIO_UPDATE => 'updated',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('groupId',$this->groupId);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('isTrash',$this->isTrash);
		$criteria->compare('starts',$this->starts,true);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('modified',$this->modified,true);

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
		));
	}
	
	/**
	 * Save a new task, runs validation
	 * @param array $attributes
	 * @return boolean
	 */
	public function draft($attributes=null) {
		$this->scenario = self::SCENARIO_DRAFT;
		$this->attributes = $attributes;
		return $this->save();
	}

	/**
	 * Save a new task, runs validation
	 * @param array $attributes
	 * @return boolean
	 */
	public function publish($attributes=null) {
		$this->scenario = self::SCENARIO_PUBLISH;
		$this->attributes = $attributes;
		return $this->save();
	}	
	
	/**
	 * Update the task, runs validation
	 * @param array $attributes
	 * @return boolean
	 */
	public function updateTask($attributes=null) {
		if($this->isExistingRecord) {
			$this->attributes = $attributes;
			return $this->save();
		}
		else {
			throw new CDbException(Yii::t('task','The task cannot be updated because it is new.'));
		}
	}
	
	/**
	 * Saves the task as trash
	 * @return boolean whether the saving succeeds.
	*/
	public function trash() {
		$this->isTrash = 1;
		$this->setScenario(self::SCENARIO_TRASH);
		return $this->save();
	}
	
	/**
	 * Saves the task as not trash
	 * @return boolean whether the saving succeeds.
	 * @see NestedSetBehavior::save()
	 */
	public function untrash() {
		$this->isTrash = 0;
		$this->setScenario(self::SCENARIO_UNTRASH);
		return $this->save();
	}
	
	public function afterFind() {
		parent::afterFind();
		
		// Format the date into a user-friendly format that matches inputs
		if(!is_null($this->startDate)) {
			$this->startDate = date('m/d/Y', strtotime($this->starts));			
		}
	}
	
	/**
	 * Delete any responses attached to the task.  
	 * @see ActiveRecord::beforeDelete()
	 */
	public function beforeDelete() {
		parent::beforeDelete();
		
		$this->scenario = self::SCENARIO_DELETE;
		
		$responses = $this->responses;
		
		try {
			foreach ($responses as $response) {
				$response->delete();
			}
		}
		catch(Exception $e) {
			Yii::log('Task before delete failed: ' . $e->getMessage(), 'error');
			throw $e;
		}
		return true;
	}

	/** 
	 * Get a truncated version of the name
	 * @return string
	 **/
	public function getShortName() {
		return StringUtils::truncate($this->name, 30);
	}
	
	/** 
	 * Get start date
	 * If internal start date is set, but time is not, internal start date is returned.
	 * Otherwise, calculated from $this->starts
	 * @return string
	 **/
	public function getStartDate() {
		if(StringUtils::isNotBlank($this->_startDate) && StringUtils::isBlank($this->_startTime)) {
			$date = new DateTime($this->starts);
			return $date->format(self::DATE_FORMAT);
		}
		elseif(StringUtils::isBlank($this->starts)) {
			return null;
		}
		
		$date = new DateTime($this->starts);
		return $date->format(self::DATE_FORMAT);
	}
	
	/** 
	 * Get start time
	 * If internal start time is set, but date is not, internal start time is returned.
	 * Otherwise, calculated from $this->starts
	 * @return string
	 **/
	public function getStartTime() {
		if(StringUtils::isBlank($this->_startDate) && StringUtils::isNotBlank($this->_startTime)) {
			return $this->_startTime;
		}
		elseif(StringUtils::isBlank($this->starts)) {
			return null;
		}
		
		$dateTimeArray = explode(' ', $this->starts);
		return $dateTimeArray[1];
	}
	
	/**
	 * @return int the Task's start date time as a datetime int
	 */
	public function getStartTimestamp() {
		if(empty($this->starts)) {
			return null;
		}
		return strtotime($this->starts);
	}

	/**
	 * @return string the time from now (in future or past) that the task starts (e.g. '1 hour ago')
	 **/
	public function getStartsFromNow() {
		Yii::app()->format->formatDateTimeAsAgo($this->startTimestamp);
	}

	public function getStartYear() {
		if(empty($this->startTimestamp)) {
			return null;
		}
		return date('Y', $this->startTimestamp);
	}

	public function getStartMonth() {
		if(empty($this->startTimestamp)) {
			return null;
		}
		return date('m', $this->startTimestamp);	
	}

	public function getStartDay() {
		if(empty($this->startTimestamp)) {
			return null;
		}
		return date('d', $this->startTimestamp);
	}

	/** 
	 * @return array of Users limited to Task::PARTICIPANT_SUMMARY_SIZE
	 **/
	public function getParticipantsSummary() {
		$participants = $this->participants;
		return array_slice($participants, 0, self::PARTICIPANT_SUMMARY_SIZE);
	}

	/** 
	 * @return int the number of additional users not included in participantsSummary
	 **/
	public function getParticipantsSummaryMoreCount() {
		return min(0, $this->participantsCount - self::PARTICIPANT_SUMMARY_SIZE);
	}

	/**
	 * @return string formatted start time
	 * @see Formatter->formatTime()
	 **/
	public function getFormattedStartTime() {
		return Yii::app()->format->formatTime($this->starts);
	}

	protected function constructStartDateTime() {
		if(StringUtils::isNotBlank($this->_startDate) && StringUtils::isNotBlank($this->_startTime)) {
			$this->starts = $this->_startDate . ' ' . $this->_startTime;
		}
		elseif(StringUtils::isBlank($this->_startDate) && StringUtils::isBlank($this->_startTime)){
			$this->starts = null;
		}
		return $this;
	}
	
	public function setStartDate($date) {
		$this->_startDate = $date;
		$this->constructStartDateTime();
	}
	
	public function setStartTime($time) {
		$this->_startTime = $time;
		$this->constructStartDateTime();
	}
	
	/**
	 * Does this task have a start time?
	 * @return boolean
	 */
	public function getHasStarts() {
		return isset($this->starts);
	}
	
	/** 
	 * @return boolean true if the public values of the task are all blank
	 */ 
	public function getIsBlank() {
		return StringUtils::isBlank($this->name) 
			&& StringUtils::isBlank($this->startDate) 
			&& StringUtils::isBlank($this->startTime);
	}

	public function getIsTrashable() {
		return $this->isExistingRecord && !$this->isTrash;
	}

	public function getIsUntrashable() {
		return $this->isExistingRecord && $this->isTrash;
	}

	/**
	 * @return boolean should user be able to respond to task
	 */
	public function getIsRespondable() {
		return $this->activity->isRespondable
			&& !$this->isTrash;
	}

	/**
	 * Is the task completed?
	 * @return boolean
	 */
	public function getIsCompleted() {
		if($this->participantsCount <= 0) {
			return false;
		}
		return $this->participantsCount == $this->participantsCompletedCount;
	}

	public function getIsCommentable() {
		return $this->activity->isCommentable;
	}

	/**
	 * Increment the participant count for a task and its ancestors
	 * @param int $participantsIncrement number of times to increment participantsCount
	 * @param int $participantsIncrement number of times to increment participantsCompletedCount
	 * @return boolean
	 */
	public function incrementParticipantCounts($participantsIncrement, $participantsCompletedIncrement) {
		if(!is_numeric($participantsIncrement) || !is_numeric($participantsCompletedIncrement)) {
			throw new CDbException("Arguments must be numeric for increment participants counts");
		}

		Yii::trace("Incrementing participants by \"{$participantsIncrement}\" and \"{$participantsCompletedIncrement}\"", get_class($this));
		
		if(($participantsIncrement == 0) && ($participantsCompletedIncrement == 0)) {
			return true;
		}
		
		/* @var $task Task */
		if($this->saveCounters(
			array( // column => increment value
				'participantsCount'=>$participantsIncrement,
				'participantsCompletedCount'=>$participantsCompletedIncrement,
		))) {
			return true;
		}
		
		throw new CDbException("Task counters were not incremented");
	}
	
	/** 
	 * @return Response the current user's Response to the Task
	 */
	public function getMyResponse() {
		if(is_null($this->_myResponse)) {
			$this->_myResponse = Response::loadResponse($this->id, Yii::app()->user->id);
		}

		return $this->_myResponse;
	}

	/**
	 * Check if the current user is participating in the task
	 * and hasn't stopped (deleted the connection)
	 * @return true if user is a participant, false if not
	 */
	public function getIsUserParticipating() {
		
		if($this->myResponse->isSignedUp || $this->myResponse->isStarted) {
			return true;
		}
		return false;
	}
	
	/**
	 * Check if the current user is participating in the task
	 * and hasn't stopped (deleted the connection)
	 * @return true if user is a participant, false if not
	 */
	public function getIsUserComplete() {
		
		if($this->myResponse->isCompleted) {
			return true;
		}
		return false;
	}
	
	public function defaultScope() {
		$table = $this->getTableAlias(false, false);

		return array(
			'order' => "{$table}.starts ASC, {$table}.created ASC"
		);
	}

	/**
	 * Tasks which are not alive
	 **/
	public function scopeNotTrash() {
		$table = $this->getTableAlias(false);

		$this->getDbCriteria()->mergeWith(array(
			'condition' =>  "{$table}.isTrash=0",
		));
		return $this;
	}
	
	/**
	* Scope for events taking place in a particular Month
	* @param mixed $month as integer (January = 1, Dec = 12)
	* @param mixed $year as integer
	*/
	public function scopeByMonth($month) {

		$starts = $month->firstDateTime;
		$ends = $month->lastDateTime;
	
		return $this->scopeStartsBetween($starts, $ends);
	}
	
	/**
	 * Scope for events taking place in a particular Month
	 * @param DateTime $starts start time
	 * @param DateTime $ends end time
	 * @return ActiveRecord the Task
	 */
	public function scopeStartsBetween(DateTime $starts, DateTime $ends) {
		$table = $this->getTableAlias(false);

		$this->getDbCriteria()->mergeWith(array(
				'condition'=>"{$table}.starts <= :ends AND {$table}.starts >= :starts",
				'params' => array(
					':starts' => $starts->format("Y-m-d H:i:s"),
					':ends' => $ends->format("Y-m-d H:i:s"),
		),
		));
		return $this;
	}
	
	/**
	 * Scope definition for events that share group value with
	 * the user's groups
	 * @param int $userId
	 * @return ActiveRecord the Task
	 */
	public function scopeUsersGroups($userId) {
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 'id IN (SELECT id FROM ' . $this->tableName() 
				.  ' WHERE groupId IN (SELECT groupId FROM ' . membership::model()->tableName()
				. ' WHERE userId=:userId))',
			'params' => array(':userId' => $userId)
		));
		return $this;
	}
	
	/**
	 * Named scope. Gets the nodes that have no start value.
	 * @return ActiveRecord the Task
	 */
	public function scopeFuture() {
		$this->getDbCriteria()->mergeWith(array(
			'condition' => 'starts >= NOW()',
		));
		return $this;
	}

	public function scopeHasStarts() {
		$this->getDbCriteria()->mergeWith(array(
			'condition'=>'starts IS NOT NULL',
			)
		);
		return $this;
	}

	/**
	 * Named scope. Gets the nodes that have no start value.
	 * @return ActiveRecord the Task
	 */
	public function scopeSomeday() {
		$this->getDbCriteria()->mergeWith(array(
			'condition'=>'starts IS NULL',
			)
		);
		return $this;
	}
	
	/**
	 * Named scope. Tasks which are not completed
	 */
	public function scopeNotCompleted() {
		$table = $this->getTableAlias(false);

		$this->getDbCriteria()->mergeWith(array(
			'condition'=>"({$table}.participantsCount = 0 OR ({$table}.participantsCount != {$table}.participantsCompletedCount))",
		));
		return $this;
	}

	/**
	 * @see LoggableRecord
	 **/
	public function getFocalModelClassForLog() {
		return get_class($this);
	}

	/**
	 * @see LoggableRecord
	 **/
	public function getFocalModelIdForLog() {
		return $this->primaryKey;
	}

	/**
	 * @see LoggableRecord
	 **/
	public function getFocalModelNameForLog() {
		return $this->name;
	}
		
	public function getWhoToNotifyByEmail()
	{
		//go through group and store in array with all active users
		if($this->groupId) {
			$group = Group::model()->findByPk($this->groupId);
			$users = $group->getMembersByStatus(User::STATUS_ACTIVE);
			return $users->data;
		}
		return array();
	}

    public function getNameForEmails() {
        return $this->name;
    }

    public function getFacebookGroupPostName() {
        return $this->name;
    }

	public function getFacebookPostId() {
		return $this->activity->facebookPostId;
	}

	public function setFacebookPostId($facebookPostId) {
		// do nothing, we don't care about the task comment id
	}

    public function getViewURL() {
    	return Yii::app()->createAbsoluteUrl('task/view',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getActivityURL() {
    	return Yii::app()->createAbsoluteUrl('activity/view',
			array(
				'id'=>$this->activity->id,
				'#'=>'task-' . $this->id,
			)
		);
    }

    public function getUpdateURL() {
		return Yii::app()->createAbsoluteUrl('task/update',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getFeedURL() {
		return Yii::app()->createAbsoluteUrl('task/feed',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getTrashURL() {
    	return Yii::app()->createAbsoluteUrl('task/trash',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getUntrashURL() {
    	return Yii::app()->createAbsoluteUrl('task/untrash',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getSignupURL() {
    	return Yii::app()->createAbsoluteUrl('task/signup',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getStartURL() {
    	return Yii::app()->createAbsoluteUrl('task/start',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getCompleteURL() {
    	return Yii::app()->createAbsoluteUrl('task/complete',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getResumeURL() {
    	return Yii::app()->createAbsoluteUrl('task/resume',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getQuitURL() {
    	return Yii::app()->createAbsoluteUrl('task/quit',
			array(
				'id'=>$this->id,
			)
		);
    }

    public function getIgnoreURL() {
    	return Yii::app()->createAbsoluteUrl('task/ignore',
			array(
				'id'=>$this->id,
			)
		);
    }
}