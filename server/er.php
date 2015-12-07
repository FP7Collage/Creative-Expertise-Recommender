<?php
////////////////////////////////////////////////////////////////////////////////
// Prototype for Inspirational Expertise Recommender
// Germán Sánchez (GREC-ESADE), Collage, february 2014
////////////////////////////////////////////////////////////////////////////////

// La idea es que las consultas a este php (webservice) no utilicen la cookie
$webService = TRUE;

// Debug mode. BE CAREFULE: if activated, the output will not be valid JSON!
if (!isset($_REQUEST['debugar'])) {
  $debugar = FALSE;
} else {
  if ($_REQUEST['debugar'] == 1) {
    $debugar = TRUE;
  }
}

require_once('version_database.php');  // also in main.php

// Use CAS in web service? If demo version, NO!
if (!isset($_REQUEST['demo'])) {
  $demoCER = FALSE;  // By default, no demo
} else {
  $demoCER = $_REQUEST['demo'] * 1;
}
$useCAS = TRUE;
if ($demoCER) {
  $useCAS = FALSE;
}

// Load the settings from the central config file
require_once 'config.php';
// Load general functions
require_once 'functions.php';
// Check login in CAS
//require_once 'login.php';
if ($useCAS) require_once 'auth.php';
// Load specific functions
require_once 'profile_functions.php';
require_once 'interaction_functions.php';
require_once 'identification_functions.php';
require_once 'selection_functions.php';
require_once 'functions_db.php';
$conn = conexion();



// Load specific functions
require_once 'er_functions.php';

if (!isset($op)) {
  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : null ;
}
switch($op) {
  case ('skills'):
    // Gets the list of available skills
    $output = getSkills();
  break;
  case ('subskills'):
  case ('backgrounds'):
    // Gets the list of available subskills
    $output = getSubskills();
  break;
  case ('request'):
    // Gets a recommendation according to the request
    $output = getRecommendation();
  break;
  case ('translate'):
    // Translate the request to specific requirements
    $output = getTranslation();
  break;
  case ('getUserInfo'):
    // Gets basic info about the candidate
    $output = getUserInfo($_REQUEST['user']);
  break;
  case ('deleteUser'):
    // Deletes the user
    $userToDelete = isset($_REQUEST['userToDelete']) ? $_REQUEST['userToDelete'] : null ;
    $output = deleteCandidate($userToDelete);
  break;
	default:
	  $output = 'empty request';
  	$op = 'nothing';
	break;
  
}

// Print the JSON result
echo $output;
?>