<?php

// debug mode on
defined('YII_DEBUG') or define('YII_DEBUG', true);

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

// This is the test Web application configuration. Any writable
// CWebApplication properties can be configured here.
// Overrides any settings from main.inc.php
return CMap::mergeArray(
	require(dirname(__FILE__).'/web.php'),
	array(
		'components'=>array(

			'clientScript'=>array(
				'minScriptLmCache'=>false, // don't cache in development
			),

	
			'db'=>array(
				'connectionString' => 'mysql:host=mysql.ajsharma.dev.enactivity.com;dbname=poncla_alpha',
				'emulatePrepare' => true,
				'enableProfiling'=>true,
				'username' => 'poncla_alpha',
				'password' => 'alpha123',
				'charset' => 'utf8',
				'enableParamLogging'=>true,
			),

			'FB'=>array(
				'appID' => '163029810507491',
				'appSecret' => 'e00d0f1d1353df24d6ff3c86cb4766b5',
				'appNamespace' => 'enactivity_test',
	        ),
			
			'log'=>array(
				'class'=>'CLogRouter',
				'routes'=>array(
					// output errors and warning to runtime file
					array(
						'class'=>'CFileLogRoute',
						'filter' => array(
							'class' => 'CLogFilter',
							'logUser' => true,
							'prefixSession' => true,
							'prefixUser' => true,
						),
						'levels'=>'error, warning',
					),
					// show log messages on web pages
					array(
						'class'=>'CWebLogRoute',
						'filter' => array(
							'class' => 'CLogFilter',
							'logUser' => true,
							'prefixSession' => true,
							'prefixUser' => true,
						),
					),
					array(
	                    'class'=>'CProfileLogRoute',
	                    'report'=>'summary',
	                ),
				),
			),
		
			// 'urlManager'=>array(
			// 	'rules'=>null, //to allow gii
			// ),
		),
		
		'modules'=>array(
			// uncomment the following to enable the Gii tool
			// custom url manager must also be disabled
			'gii'=>array(
				'class'=>'system.gii.GiiModule',
				'password'=>'notsochewy',
				// 'ipFilters'=>false,
			),
		),
	)
);