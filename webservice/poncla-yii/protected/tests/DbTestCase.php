<?php

require_once 'TestConstants.php';

/**
 * The base class for database test cases.
 * In this class, we set the base URL for the test application.
 * We also provide some common methods to be used by concrete test classes.
 */
class DbTestCase extends CDbTestCase
{
	public $fixtures = array(
		'groupFixtures'=>':group',
		'userFixtures'=>':user',
		'groupUserFixtures'=>':group_user',
    );
    
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
	}
    
	/**
	 * Sets up before each test method runs.
	 * This mainly sets the base URL for the test application.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		// login as registered user
		$loginForm = new UserLoginForm();
		$loginForm->email = USER_EMAIL; //declared in init.php
		$loginForm->password = USER_PASSWORD;
		if(!$loginForm->login()) {
			throw new Exception("Could not login in setup");
		}
	}
	
	protected function tearDown()
	{
		parent::tearDown();
		Yii::app()->user->logout(true);
	}
 
    public static function tearDownAfterClass()
    {
    	parent::tearDownAfterClass();
    }
}
