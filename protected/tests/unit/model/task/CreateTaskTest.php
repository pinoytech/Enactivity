<?php

require_once 'TestConstants.php';

class CreateTaskTest extends DbTestCase
{

	public $fixtures = array(
		'groupFixtures'=>':group',
		'userFixtures'=>':user',
		'groupUserFixtures'=>':group_user',
		'taskFixtures'=>':task',
	);
	
	public function setUp()
	{
		parent::setUp();
		
		// login as user
		$loginForm = new UserLoginForm();
		$loginForm->email = $this->userFixtures['registered']['email'];
		$loginForm->password = 'test';
		$loginForm->login();
	}
	
	/**
	* Create a valid task
	*/
	public function testCreateTaskValidScenarioDefault() {
		
		// test parameters
		$id = 42;
		$parentId = $this->taskFixtures['noparenttask']['id'];
		$name = "test task";
		$isTrash = 0;
		$created = date ("Y-m-d H:i:s", strtotime("-1 hours"));
		$modified = date ("Y-m-d H:i:s", strtotime("-1 hours"));
		
		$testdata = array(
			'id' => $id,
			'parentId' => $parentId,
			'name' => $name,
			'isTrash' => $isTrash,
			'created' => $created,
			'modified' => $modified,
		);
		
		// run test
		$task = new Task();
		
		$this->assertEquals(Task::SCENARIO_INSERT, $task->scenario, "Task's default scenario is not " . Task::SCENARIO_INSERT);
		
		$task->attributes = $testdata;
		
		// confirm attribute assigned properly
		$this->assertNull($task->id, 'task id attribute was assigned in [' . $task->getScenario() . ']');
		$this->assertNotNull($task->parentId, 'task parentId attribute was not assigned in [' . $task->getScenario() . ']');
		$this->assertNotNull($task->name, 'task name attribute was not assigned in [' . $task->getScenario() . ']');
		$this->assertNull($task->priority, 'task priority attribute was assigned in [' . $task->getScenario() . ']');
		$this->assertNotNull($task->isTrash, 'task isTrash attribute was not assigned in [' . $task->getScenario() . ']');
		$this->assertNull($task->created, 'task created attribute was assigned in [' . $task->getScenario() . ']');
		$this->assertNull($task->modified, 'task modified attribute was assigned in [' . $task->getScenario() . ']');
		
		// confirm validation & save works
		$this->assertTrue($task->validate(), 'valid task was not validated: ' . print_r($task->getErrors(null), true));
		$this->assertTrue($task->save(), 'valid task was not saved');
	}
	
	/**
	* Create a valid task and test if name is null
	*/
	public function testCreateTaskWithNullName() {
		
		$name = null;
		$parentId = $this->taskFixtures['noparenttask']['id'];
		
		$task = new Task();
		$task->setAttributes(array(
			'name' => $name,
			'parentId' => $parentId,
		));
		
		$this->assertNull($task->name, 'save name is not null');
		$this->assertFalse($task->validate(), 'task without name was validated: ' . print_r($task->getErrors(null), true));
	}
	
	/**
	* Create a valid task and ensure entries are trimmed
	*/
	public function testCreateTaskTrimSpaces() {
		
		$name = "Test Task";
		$paddedName = ' ' . $name . ' ';
		$parentId = $this->taskFixtures['noparenttask']['id'];
		
		$task = new Task();
		$task->setAttributes(array(
			'name' => $paddedName,
			'parentId' => $parentId,
		));
		
		$this->assertTrue($task instanceof Task, 'found task not a Task object');
		$this->assertTrue($task->save(), 'valid task was not saved: ' . print_r($task->getErrors(null), true));
		$this->assertNotNull($task->created, 'task created was not set');
		$this->assertNotNull($task->modified, 'task modified was not set');
			
	}
	
	/**
	* Set name input over the acceptable lengths
	*/
	public function testCreateTaskExceedMaximumNameInput() {
		
		$name = StringUtils::createRandomString(255 + 1);
		$parentId = $this->taskFixtures['noparenttask']['id'];

		$task = new Task();
		$task->setAttributes(array(
			'name' => $name,
			'parentId' => $parentId,
		));
		
		$this->assertFalse($task->validate(), 'task with name of 256 characters was validated: ' . print_r($task->getErrors(null), true));
		$this->assertFalse($task->save(), 'task with name of 256 characters was saved');
	}
	
	/**
	* Test task create when no name, starts and ends are set
	*/
	public function testCreateTaskNoInput() {

		$name = null;
		$parentId = $this->taskFixtures['noparenttask']['id'];
		
		$task = new Task();
		$task->setAttributes(array(
			'name' => $name,
			'parentId' => $parentId,
		));
		
		$this->assertNull($task->name, 'unsaved task has a name');
		$this->assertFalse($task->validate(), 'task with no inputs was saved');
	}
	
	/**
	 * Test task create when start date is after end date
	 */
	public function testCreateTaskStartDateAfterEndDate() {
		
		$name = $this->taskFixtures['noparenttask']['name'];
		$parentId = $this->taskFixtures['noparenttask']['id'];
		$starts = date ("Y-m-d H:i:s", strtotime("-1 hours"));
		$ends = date ("Y-m-d H:i:s", strtotime("-2 hours"));
		
		$task = new Task();
		$task->setAttributes(array(
			'name' => $name,
			'parentId' => $parentId,
			'starts' => $starts,
			'ends' => $ends,
		));
		
		$this->assertFalse($task->validate(), 'task with start date after end date was validated');
		$this->assertFalse($task->save(), 'task with start date after end date saved');
	}
	
	/**
	 * Test that priority is auto-assigned on validate
	 */
	public function testPriorityAutoAssign() {
		
		$name = "test task";
		$parentId = $this->taskFixtures['noparenttask']['id'];
		
		$task = new Task();
		$task->setAttributes(array(
			'name' => $name,
			'parentId' => $parentId,
		));
		
		$this->assertNull($task->priority, 'Priority was set before validate');
		$task->validate();
		$this->assertNotNull($task->priority, 'Priority was not set before validation');
	}
}