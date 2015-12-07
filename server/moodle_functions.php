<?php
/**
 * MOODLE module
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, april 2014
 *
 * Public functions:
 *   Moodle_GetUsersList()
 *   Moodle_GetEnroledUsersList ($courseid)
 *   Moodle_CheckEnrolment($userid, $courseid)
 *
 * Private functions:
 *   MoodleRequest($op, ...)
 */

function Moodle_CheckEnrolment($userid, $courseid) {
/**
 * Checks if the user is enroled in the course
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 24.04.2014
 */
  global $debugar;

  if ($debugar) echo 'Checking user '.$userid.' in course '.$courseid.'<br />';
  
  $enrUsers = Moodle_GetEnroledUsersList ($courseid);
  $enrUsers = objectToArray($enrUsers);
  // $moodleUsers es un array de users, donde cada uno tiene varios campos
  // como id, username ...
  
  if ($debugar) echo 'Enroled users:<pre>'.print_r($enrUsers, TRUE).'</pre>';
  $key = array_search($userid, array_column($enrUsers, 'id')); 
  if ($debugar) echo '<h1>key = '.$key.'</h1>';
  if ($key != NULL) {
    return(TRUE);
  } else {
    return(FALSE);
  }  
}

function Moodle_GetUserId($usert) {
/**
 * Returns the moodle id of the username $usert
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 24.04.2014
 */
  global $debugar;
  $moodleUsers = Moodle_GetUsersList();
  $moodleUsers = objectToArray($moodleUsers);
  
  // $moodleUsers es un array con 'users, que es otro array:
  // [0][1]... con [id] y [username]
  //echo '<pre>'.print_r($moodleUsers['users'], TRUE).'</pre>';
  
  $key = array_search($usert, array_column($moodleUsers['users'], 'username')); 
  if ($debugar) echo '<h1>key = '.$key.'</h1>';
  if ($key != NULL) {
    $userid = $moodleUsers['users'][$key]['id'];
  } else {
    $userid = NULL;
  }
  if ($debugar) echo '<h1>userid = '.$userid.'</h1>';
  if ($debugar) echo '<pre>'.print_r($moodleUsers['users'], TRUE).'</pre>';
  return($userid);
}

// =============================================================================
function MoodleRequest($op, $initialParam = NULL, $json = FALSE) {
/**
 * Makes a request to the API of Moodle service.
 * If $param, it makes a POST request.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 11.03.2014
 */
  global $debugar;
  
  $url = 'http://moodle.projectcollage.eu/webservice/rest/server.php';

  $param = 'wstoken=e85ad37a66710486d3ced2fab08de5da';
  $param .= '&moodlewsrestformat=json';
  $param .= '&wsfunction='.$op;
  if ($initialParam != NULL) $param .= '&'.$initialParam;

  $paramEncoded = urlencode($param);
  if ($debugar) echo ('param = '.$param.'<br />paramEncoded = '.$paramEncoded.'<br />');
//        'header' => "Authorization: {$authToken}\r\n".

  if ($debugar) echo ('requestER: getting '.$url. '<br />'."\n");
  if (!$param == null) {
    $opts = array(
      'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            //'header'  => 'Content-type: application/json',
            'content' => $param
        )
    );
  } else {
    $opts = array(
      'http' => array(
            'method'  => 'GET',
            //'header'  => 'Content-type: application/x-www-form-urlencoded'
            'header'  => 'Content-type: application/json'
        )
    );
  }
  //echo '<p>$opts: ';
  //var_dump($opts);
  //echo '</p>';
  $context = stream_context_create($opts);
  
  if ($debugar) {
    //echo('<p>Downloading "'.$url.': '.$url.'", param='.$param.'</p>');
  }
  if ($debugar) echo ('MoodleRequest: final URL requested: '.$url. '<br />'."\n");
  $res = file_get_contents($url, false, $context);
  if ($debugar) echo ('MoodleRequest: response: '.$res. '<br />'."\n");
  if ($json) {
    return($res);
  } else {
    return(json_decode($res));
  }
}

// =============================================================================
function Moodle_GetUsersList ($json = FALSE) {
/**
 * Gets the list of users registered in CAS/Moodle.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 17.04.2015
 */
  $param = 'criteria[0][key]=auth&criteria[0][value]=cas';
  $list = MoodleRequest($op = 'core_user_get_users', $param = $param, $json = $json);
  return($list);
}
function Moodle_GetEnroledUsersList ($courseid, $json = FALSE) {
/**
 * Gets the list of users enroled in course $courseid.
 * Makes a request to the API of Moodle service.
 * If $param, it makes a POST request.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 17.04.2015
 */
  $param = 'courseid='.$courseid;
  $list = MoodleRequest($op = 'core_enrol_get_enrolled_users', $param = $param, $json = $json);
  return($list);
}
function Moodle_EnrolUser ($courseid, $userid, $roleid = 5, $json = FALSE) {
/**
 * Gets the list of users enroled in course $courseid.
 * Makes a request to the API of Moodle service.
 * If $param, it makes a POST request.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 17.04.2015
 */
  $param = 'enrolments[0][roleid]='.$roleid.'&enrolments[0][courseid]='.$courseid.'&enrolments[0][userid]='.$userid;
  $list = MoodleRequest($op = 'enrol_manual_enrol_users', $param = $param, $json = $json);
  return($list);
}
function Moodle_UnenrolUser ($courseid, $userid, $json = FALSE) {
/**
 * Gets the list of users enroled in course $courseid.
 * Makes a request to the API of Moodle service.
 * If $param, it makes a POST request.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 17.04.2015
 */
  $param = 'enrolments[0][courseid]='.$courseid.'&enrolments[0][userid]='.$userid;
  $list = MoodleRequest($op = 'enrol_manual_unenrol_users', $param = $param, $json = $json);
  return($list);
}
?>