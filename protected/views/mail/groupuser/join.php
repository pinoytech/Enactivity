<?php 
/**
 * View for groupuser model join scenario
 * 
 */

// calculate article class
$articleClass = "view";
$articleClass .= " email";

// start article
echo PHtml::openTag('article', array(
	'class' => 'email'
));

// created date
echo PHtml::openTag('p');
echo PHtml::openTag('strong');
echo PHtml::openTag('time');
echo PHtml::encode(
	//FIXME: use actual event time
	Yii::app()->format->formatDateTime(time())
);
echo PHtml::closeTag('time');
echo PHtml::closeTag('strong');
echo PHtml::closeTag('p');
echo PHtml::openTag('p');

//Great news! [firstName][lastName] joined [groupName].
//echo "Great news! " . PHtml::encode($user->fullName) . " joined " . PHtml::encode($data->name) . ".";

foreach($changedAttributes as $header)
{
	echo "Great news! " . PHtml::encode($user->fullName) . " joined " . PHtml::encode($data->name) . ".";
}

echo PHtml::closeTag('article');
?>