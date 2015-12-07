<?php
/**
 * INTERACTION modules
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, march 2014
 *
 * Public:
 *  json_translateRequestToRequirements($jrequest, $userid): traduce las
 *    preferencias escogidas por el usuario a requirements.
 *  translateRequestToRequirements($userid)
 */
require_once 'profile_functions.php';
require_once('define_'.$versionDataBase.'.php');

function json_translateRequestToRequirements($jrequest, $userid) {
/**
 * Translates the preferences ($jrequest) of the user to requirements.
 * If mainskills == 'implicit', set skills requirements according to the own
 * data of the $userid
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 29.04.2014
 */
  //echo $jrequest;
  $request = objectToArray(json_decode($jrequest));
  //echo '<pre>'.print_r($request, TRUE).'</pre>';

  global $debugar;
  
  $mainSkills           = $request['mainskills'];
  $skills               = $request['skills'];
  $skillsLevels         = $request['skills_levels'];
  $qualifications       = $request[STRING_SUBSKILLS];
  $qualificationsLevels = $request['qualifications_levels'];
  $proximity            = $request['proximity'];
  $department           = $request['department'];
  $userRequest          = $request['user'];
    
  $content = '<h2>Parameters:</h2>';
  $requirements = array();
  // Skills
  if ($mainSkills == 'implicit') {
    // Implicit skills: same as users'.
    //$userSkills = getCandidateProfile($userid)['skills'];
    $userSkills = getCandidateProfile($userRequest)['skills']; // new
    $skills = array(); $skillsLevels = array();
    foreach ($userSkills as $userSkill) {
      $skills[] = $userSkill['id'];
      $skillsLevels[] = $userSkill['level'];
    }
  }
  
  // Explicit skills
  if (!isset($skills)) {
    $content .= '<p>No expertise skills selected</p>';
  } else {
    $content .= '
      <p>Skills: 
    ';
    //var_dump($request);
    //echo '<pre>'.print_r($request).'</pre>';
    $requirements['skills'] = array();
    $i = 0;
    foreach ($skills as $selectedOption) {
      $content .= $selectedOption.'-'.$skillsLevels[$i].' ';
      if ($selectedOption > 0) {
        // Set the variable at maximum requirement
        $requirements['skills'][] = array('id' => $selectedOption, 'level' => $skillsLevels[$i]);
      }
      $i++;
    }
    $content .= '</p>'."\n";
  }
      
  // Subskills
  if (!isset($qualifications)) {
    $content .= '<p>No '.STRING_SUBSKILLS.' selected</p>';
  } else {
    $content .= '
      <p>'.may1a(STRING_SUBSKILLS).':
    ';
    $requirements[STRING_SUBSKILLS] = array();
    $i = 0;
    foreach ($qualifications as $selectedOption) {
      $content .= $selectedOption.' ';
      if ($selectedOption > 0) {
        // Set the variable at maximum requirement
        //$requirements['qualifications'][] = array('id' => $selectedOption, 'level' => $qualificationsLevels[$i]);
        $requirements[STRING_SUBSKILLS][] = array('id' => $selectedOption);
      }
      $i++;
    }
    $content .= '</p>'."\n";
  }
  
  // Proximity
  if (isset($proximity)) {
    // Ensure that the proximity required is valid
    if ($proximity != 'high' and $proximity != 'low' and $proximity != 'disable') {
      $proximity = 'disable';
    }
    $content .= '
      <p>Proximity to dept. '.$department.': '.$proximity.'</p>
    ';
    $requirements['proximity'] = array('level' => $proximity, 'id' => $department);
  }
  
  // Availability (by now, always high)
  $requirements['availability'] = array('level' => 'high');

  // User that has made the request:
  $requirements['userRequest'] = $userRequest;
  if ($debugar) {
    echo $content;
  }  
  return($requirements);
}

// =============================================================================
function translateRequestToRequirements_OLD($userid) {
/**
 * Translates the preferences ($_REQUEST) of the user to requirements.
 * If mainskills == 'implicit', set skills requirements according to the own
 * data of the $userid
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 11.03.2014
 */
  global $debugar;
  
  $mainSkills           = $_REQUEST['mainskills'];
  $skills               = $_REQUEST['skills'];
  $skillsLevels         = $_REQUEST['skills_levels'];
  $qualifications       = $_REQUEST[STRING_SUBSKILLS];
  $qualificationsLevels = $_REQUEST['qualifications_levels'];
  $proximity            = $_REQUEST['proximity'];
    
  $content = '<h2>Parameters:</h2>';
  $requirements = array();
  // Skills
  if ($mainSkills == 'implicit') {
    // Implicit skills: same as users'.
    $userSkills = getCandidateProfile($userid)['skills'];
    $skills = array(); $skillsLevels = array();
    foreach ($userSkills as $userSkill) {
      $skills[] = $userSkill['id'];
      $skillsLevels[] = $userSkill['level'];
    }
  }
  
  // Explicit skills
  if (!isset($skills)) {
    $content .= '<p>No expertise skills selected</p>';
  } else {
    $content .= '
      <p>Skills: 
    ';
    //var_dump($_REQUEST);
    //echo '<pre>'.print_r($_REQUEST).'</pre>';
    $requirements['skills'] = array();
    $i = 0;
    foreach ($skills as $selectedOption) {
      $content .= $selectedOption.'-'.$skillsLevels[$i].' ';
      if ($selectedOption > 0) {
        // Set the variable at maximum requirement
        $requirements['skills'][] = array('id' => $selectedOption, 'level' => $skillsLevels[$i]);
      }
      $i++;
    }
    $content .= '</p>'."\n";
  }
      
  // Subskills
  if (!isset($qualifications)) {
    $content .= '<p>No '.STRING_SUBSKILLS.' selected</p>';
  } else {
    $content .= '
      <p>'.may1a(STRING_SUBSKILLS).':
    ';
    $requirements[STRING_SUBSKILLS] = array();
    $i = 0;
    foreach ($qualifications as $selectedOption) {
      $content .= $selectedOption.' ';
      if ($selectedOption > 0) {
        // Set the variable at maximum requirement
        //$requirements['qualifications'][] = array('id' => $selectedOption, 'level' => $qualificationsLevels[$i]);
        $requirements[STRING_SUBSKILLS][] = array('id' => $selectedOption);
      }
      $i++;
    }
    $content .= '</p>'."\n";
  }
  
  // Proximity
  if (isset($proximity)) {
    // Ensure that the proximity required is valid
    if ($proximity != 'high' and $proximity != 'low' and $proximity != 'disable') {
      $proximity = 'disable';
    }
    $content .= '
      <p>Proximity: '.$proximity.'</p>
    ';
    $requirements['proximity'] = array('level' => $proximity);
  }
  
  // Availability (by now, always high)
  $requirements['availability'] = array('level' => 'high');

  if ($debugar) {
    echo $content;
  }  
  return($requirements);
}
?>