<?php
/**
 * Class file for EmailNotificationBehavior
 */

Yii::import("application.components.ar.db.EmailableRecord");
Yii::import("application.components.notifications.NotificationBehavior");

/**
 * This is the behavior class for behavior "EmailNotificationBehavior".
 * The EmailNotificationBehavior implements EmailRecord Model
 */
class EmailNotificationBehavior extends NotificationBehavior
{
	public function getEnabled() {
		return Yii::app()->params['ext.behaviors.model.EmailNotificationBehavior.enabled'];
	}


	public function afterSave($event)
	{
		if($this->enabled && $this->isNotifiableScenario && isset(Yii::app()->user)) {

			$message = Yii::app()->mailer->constructMessage();

			$view = strtolower(get_class($this->owner)). '/' . $this->owner->scenario;
			
			$message->setBody($view, array(
				'data'=>$this->owner, 
				'changedAttributes'=>$this->owner->getChangedAttributes($this->scenarioAttributes),
				'user'=>Yii::app()->user->model
			));

			$message->setSubject($this->composeSubject());	
			
			$message->from = $this->composeFrom();

			$users = $this->owner->whoToNotifyByEmail();
			
			$this->sendMessage($message, $users);
		}
	}

	public function afterDelete($event) {

		if($this->enabled && $this->isNotifiableScenario && isset(Yii::app()->user)) {
			
			$message = Yii::app()->mailer->constructMessage();
			$view = strtolower(get_class($this->owner)). '/delete';
			$message->setBody($view, array(
				'data'=>$this->owner, 
				'user'=>Yii::app()->user->model
			));

			$message->setSubject(PHtml::encode(Yii::app()->format->formatDateTime(time())) . ' something was deleted on ' . Yii::app()->name . '!');
			$message->from = $this->composeFrom();

			$users = $this->owner->whoToNotifyByEmail();
			
			$this->sendMessage($message, $users);
		}
	}

	public function composeFrom() {
		return 'notifications@' . CHttpRequest::getServerName();
	}

	/**
 	 * After the model saves, record the attributes
	 * @param CEvent $event
	 */
	public function composeSubject() {
		// based on the given scenario, construct the appropriate subject
		$label = $this->owner->getScenarioLabel($this->owner->scenario);
		$name = $this->owner->emailName;
		$userName = Yii::app()->user->model->fullName;
		return $userName . " " . $label . " " . $name;
	}

	/** 
	 * Send the email to all users
	 * @param MailMessage
	 * @param array of Users
	 */
	public function sendMessage($message, $users = array()) {
		foreach($users as $user) {
			if(Yii::app()->params['ext.behaviors.model.EmailNotificationBehavior.notifyCurrentUser']
			 || strcasecmp($user->id, Yii::app()->user->id) != 0) {
				$message->addTo($user->email);
			}
		}
		Yii::app()->mailer->batchSend($message); 
	}
}