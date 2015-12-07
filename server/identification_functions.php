<?php
/**
 * IDENTIFICATION modules
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, march 2014
 *
 * Public:
 *   getFeasibleCandidates($reqs): obtiene los perfiles de los candidatos iniciales
 */

require_once('define_'.$versionDataBase.'.php');
require_once 'client_functions.php';  // requestER

// =============================================================================
function getFeasibleCandidates($reqs = NULL) {
/**
 * Gets initial list of feasible candidates. Requirements are needed?
 * It also returns the profile of each initial candidate
 */
  global $conn, $token, $debugar, $moodle;
  
  if (FALSE) {
    // Check if "user" is inside $reqs
    if ($reqs['userRequest'] != null) {
      //$sql = 'SELECT * FROM zer_users WHERE username != \''.$reqs['userRequest'].'\'';
      $sql = 'SELECT * FROM '.TABLE_USERS;
    } else {
      //$userCred = checkTokenESB($token);
      //$sql = 'SELECT * FROM zer_users WHERE username != "'.$userCred['username'].'"';
    }
  }
  $sql = 'SELECT * FROM '.TABLE_USERS;
  
  if ($moodle) {
    // If we are in Moodle environment, the feasible candidates are the ones registered in moodle.
    // Obtain list of users (core_user_get_users)
    // Add them the the select or filter the results according them
    $url = 'http://moodle.projectcollage.eu/webservice/rest/server.php';
    if ($debugar) echo ('getFeasibleCandidates: getting '.$url. '<br />'."\n");
    $param = 'wstoken=e85ad37a66710486d3ced2fab08de5da&moodlewsrestformat=json&wsfunction=core_user_get_users&criteria[0][key]=auth&criteria[0][value]=cas';
    $candidates = requestER($url, $param);
    if ($debugar) echo ('getFeasibleCandidates: List of CAS users: '.$candidates. '<br />'."\n");
  }
  
	$result = consulta($sql, $conn);
	$out = array();
	while ($obj = pg_fetch_object ($result)) {
	  // Convert stdClass to array
	  //$candidate = objectToArray(json_decode(json_encode($obj), true));
	  $candidate = json_decode(json_encode($obj), true);
	  $candidate['profile'] = getCandidateProfile($candidate['username']); //($candidate['userid']);
	  $out[] = $candidate;
	}
	return($out);
}
?>