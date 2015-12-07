<?php
////////////////////////////////////////////////////////////////////////////////
// Prototype for Inspirational Expertise Recommender
// Germán Sánchez (GREC-ESADE), Collage, february 2014
////////////////////////////////////////////////////////////////////////////////

$mainphp = TRUE;

if (!isset($_REQUEST['debugar'])) {
  $debugar = FALSE;
} else {
  if ($_REQUEST['debugar'] == 1) {
    $debugar = TRUE;
  }
}

require_once('version_database.php');  // also in er.php

// Load the settings from the central config file
require_once 'config.php';
// Load general functions
require_once 'functions.php';
// Check login in CAS
//require_once 'login.php';
require_once 'auth.php';

//die($token.$ticket);

// Let's start
if (!isset($p)) {
  $p = isset($_REQUEST['p']) ? $_REQUEST['p'] : null ;
}

// CER for Moodle
require_once 'moodle_config.php';
//echo ('Moodle = '.$moodle);

if ($moodle) {
  if (!isset($courseid)) {
    $courseid = isset($_REQUEST['courseid']) ? $_REQUEST['courseid'] : 1 ;
  }
  if ($debugar) echo ('Received course ID:'.$courseid.'<br />');
  if (!isset($userid)) {
    $userid = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : 1 ;
  }
}

//print_r($_SERVER);

switch ($p) {
/*	case ('login'):
	$pg = "login";
	$tituloinfo = "<strong>Login</strong>\n";
	$titulo = "Login";
	break;
*/	
	case ('client2'):
	$pg = "client2";
	$tituloinfo = "<strong>Welcome</strong>\n";
	$titulo = "CER Client";
	break;
	
	case('users'):
	$pg = 'batch_profile';
	$tituloinfo = "<strong>Welcome</strong>\n";
	$titulo = "Management of CAS users";
	break;
	
	case('test'):
	$pg = 'test';
	$tituloinfo = "<strong>Welcome</strong>\n";
	$titulo = "Test";
	break;
	
	case('info_profile'):
	$pg = 'info_profile';
	$tituloinfo = "<strong>Welcome</strong>\n";
	$titulo = "Information about your Collage profile";
	break;
	
	default:
	$pg = "client";
	$tituloinfo = "<strong>Welcome</strong>\n";
	$titulo = 'CER Client';
	break;
}

require_once('header.php');
require_once($pg.'.php');
require_once('footer.php');

?>