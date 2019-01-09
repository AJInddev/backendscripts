<?php


require_once('Git.php');
require_once('../TrackersDailyCron.php');

$repo = Git::windows_mode();
$repo = Git::open('../tlist');  // -or- Git::create('/path/to/repo')
$repo->pull();

$task = new TrackersManager;
$task->getSubmittedTrackersUpdate();

try{
	$repo->add('.');
	$repo->commit('@Ranveer - Tracker Updates - '. date('Y-m-d h:i:s',strtotime('now')));
	$repo->push('origin', 'master');
}
catch(Exception $e)
{	
	print_r($e->message);
}