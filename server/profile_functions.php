<?php
/**
 * PROFILE functions
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, march 2014
 *
 * Public:
 *  getCandidateProfile($username): devuelve el perfil del candidato con $username
 *  deleteCandidate($user): elimina el perfil del candidato $user
 *  getUserInfo($user): devuelve informacion basica del usuario
 *  isCandidate($username): devuelve true si $username existe en la bbdd de candidatos
 *  getUserProfileFromP2PUM($token): obtiene los datos de P2PUM del usuario
 */
 
require_once('define_'.$versionDataBase.'.php');


// =============================================================================
function getCandidateProfile($username) {
  global $conn, $debugar, $versionDataBase;
  
  // Obtenemos el id del usuario
  $sql = 'SELECT * FROM '.TABLE_USERS.' WHERE username =\''.$username.'\'';
	$result = consulta($sql, $conn);
  $obj = pg_fetch_object ($result);
  $userid = $obj->userid;
  if ($debugar) echo ('getCandidateProfile: $sql; userid = '.$userid.'.<br />'."\n");

  // Lista de skills
  $sql = 'SELECT * FROM '.TABLE_PROFILE_SKILLS.' WHERE userid ='.$userid;
	$result = consulta($sql, $conn);
	while ($obj = pg_fetch_object ($result)) {
	  $skills[] = $obj;
  }  
  if ($debugar) echo ('getCandidateProfile: $sql; nrows = '.pg_num_rows($result).'.<br />'."\n");
  
  // Lista de subskills
  $sql = 'SELECT * FROM '.TABLE_PROFILE_SUBSKILLS.' WHERE userid ='.$userid;
	$result = consulta($sql, $conn);
	$subskills = array();
	while ($obj = pg_fetch_object ($result)) {
	  $subskills[] = $obj;
	}
  if ($debugar) echo ('getCandidateProfile: $sql; nrows = '.pg_num_rows($result).'.<br />'."\n");
  
  // Department
  if ($versionDataBase == 'waag') {
    $sql = 'SELECT id FROM '.TABLE_PROFILE_DEPARTMENT.' WHERE userid ='.$userid;
    $result = consulta($sql, $conn);
    $obj = pg_fetch_object ($result);

    //$dept = int2dept($obj->id);
    $dept = $obj->id;
  } else {
    $sql = 'SELECT dept FROM '.TABLE_PROFILE_DEPARTMENT.' WHERE userid ='.$userid;
    $result = consulta($sql, $conn);
    $obj = pg_fetch_object ($result);
    $dept = $obj->dept;
  }
  if ($debugar) echo ('getCandidateProfile: $sql; dept('.$obj->id.') = '.$dept.'.<br />'."\n");
  
  // Availability
  $sql = 'SELECT * FROM '.TABLE_PROFILE_AVAILABILITY.' WHERE userid ='.$userid;
	$result = consulta($sql, $conn);
  $obj = pg_fetch_object ($result);
  $avail = $obj->level;
  if ($debugar) echo ('getCandidateProfile: $sql; availability = '.$avail.'.<br />'."\n");

  return(objectToArray(array('skills' => $skills, STRING_SUBSKILLS => $subskills,
               'department' => $dept, 'availability' => $avail)));
}

// =============================================================================
function getUserInfo($user = NULL) {
/**
 * SERVICE for getting basic informacion of the $user stored into the CER database
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version july 2014
 */
  global $debugar, $token;

  $userToDelete = null;

  // Check if the authenticated user is the same as the one to be deleted or
  // if the user has admin privileges.
  $credentials = checkTokenESB($token);
  if (isUser($credentials)) {
    $username = $credentials['username'];
    if ($debugar) echo ('getUserInfo: username <strong>"'.$username.'"</strong> (role "'.$credentials['role'].'") wants to get profile of candidate "<strong>'.$user.'</strong>"!<br />'."\n");
    if ($user == $username) {
      $userToRetrieve = $user;
    } else {
      if ($debugar) echo ('getUserInfo: WARNING, usernames do not match.<br />'."\n");
      if (isAdmin($credentials)) {
        $userToRetrieve = $user;
      }
    }
  }
  
  if ($userToRetrieve != null) {
    // Check the existence of the user
    $isCand = isCandidate($userToRetrieve);
    if (!$isCand) {
      if ($debugar) echo ('getUserInfo: KO, although the user has permision, the candidate "'.$userToRetrieve.'" does not exist in CER databases! Response:'."\n");
      $output[] = array('infoNumber' => 4, 'infoText' => 'Candidate does not exist.');
      //echo json_encode($output);
      $output = json_encode($output);
    } else {
      $output[] = array('infoNumber' => 3, 'infoText' => 'Profile of candidate '.$userToRetrieve.' exists.', 'infoDept' => $isCand);
      $output = json_encode($output);
    }
  }  
  return ($output);
}

// =============================================================================
function deleteCandidate($user = NULL, $type = 'all') {
/**
 * SERVICE for deleting the information of the $user stored into the CER database
 * If $type != "all", it deletes just the requested type
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version june 2014
 */
  global $debugar, $token;
  
  //$output = 'User to delete: '.$user.'.';
  
  $userToDelete = null;
  
  // Check if the authenticated user is the same as the one to be deleted or
  // if the user has admin privileges.
  $credentials = checkTokenESB($token);
  if (isUser($credentials)) {
    $username = $credentials['username'];
    if ($debugar) echo ('deleteCandidate: username <strong>"'.$username.'"</strong> (role "'.$credentials['role'].'") wants to delete profile of candidate "<strong>'.$user.'</strong>"!<br />'."\n");
    if ($user == $username) {
      $userToDelete = $user;
    } else {
      if ($debugar) echo ('deleteCandidate: WARNING, usernames do not match.<br />'."\n");
      if (isAdmin($credentials)) {
        $userToDelete = $user;
      }
    }
  }

  if ($userToDelete != null) {
    // Check the existence of the user
    if (!isCandidate($userToDelete)) {
      if ($debugar) echo ('deleteCandidate: KO, although the user has permision, the candidate "'.$userToDelete.'" does not exist! Response:'."\n");
      $output[] = array('errorNumber' => 3, 'errorText' => 'Candidate to delete does not exist.');
      echo json_encode($output);
    }
    $txtType = '';
    if ($type != 'all') {
      $txtType = '(part '.$type.') ';
    }
    if ($debugar) echo ('deleteCandidate: OK, profile of candidate "'.$userToDelete.'" would be deleted.<br />'."\n");
    $output[] = array('infoNumber' => 2, 'infoText' => 'Profile '.$txtType.'of candidate '.$userToDelete.' would be deleted (username '.$username.').');
    $output = json_encode($output);
  }

  return ($output);
}

// =============================================================================
function isCandidate($username) {
  global $conn, $debugar, $token, $versionDataBase;
  
  // Obtenemos el id del usuario
  $sql = 'SELECT * FROM '.TABLE_USERS.' WHERE username =\''.$username.'\'';
	$result = consulta($sql, $conn);
  $quants = pg_num_rows($result);
  if ($quants == 0) {
    // Comprobamos si tiene perfil en P2PUM
    $profile = getUserProfileFromP2PUM($token);
    return(FALSE);
  } else {
    $obj = pg_fetch_object ($result);
    $userid = $obj->userid;
    # Devolvemos el departamento
    if ($versionDataBase == 'waag') {
      $sql = 'SELECT id FROM '.TABLE_PROFILE_DEPARTMENT.' WHERE userid ='.$userid.'';
      $result = consulta($sql, $conn);
      $obj = pg_fetch_object($result);
      return($obj->id);
    } else {
      $sql = 'SELECT dept FROM '.TABLE_PROFILE_DEPARTMENT.' WHERE userid ='.$userid.'';
      $result = consulta($sql, $conn);
      $obj = pg_fetch_object($result);
      return($obj->dept);
    }
  }
}


// =============================================================================
function getUserProfileFromP2PUM($token) {
  global $debugar;
  
  if ($debugar) echo ('getUserProfileFromP2PUM: getting profile info with token='.$token.'...<br />'."\n");
  $profile = json_decode(
    @file_get_contents(
      'http://explicit.p2pum.imuresearch.eu/p2pum/skills?ticket='.$token
    ),
    true
  );
  echo '<pre>'.print_r($profile, true).'</pre>';

  // Translates the profile to CER's format
  $vSkillls = array(); $vSubskills = array();

  if ($profile['formCompleted'] == 1) {
    // The user has completes P2PUM form
    // Skills
    foreach ($profile['skills'] as $skill) {
      $v = explode(':', $skill);
      switch($v[1]) {
        case 'research':
          $id = 4;
          break;
        case 'development':
          $id = 6;
          break;
        default:
          $id = 0;
          break;
      }        
      if ($v[2] >= 4) {
        $level = 'high';
      } elseif ($v[2] <= 1) {
        $level = 'low';
      } else {
        $level = 'medium';
      }
      $vSkillls[] = array('id'=>$id, 'level'=>$level);
    }
    // Subskills
    foreach ($profile['tools'] as $skill) {
      $v = explode(':', $skill);
      switch($v[1]) {
        case 'research':
          $id = 4;
          break;
        case 'development':
          $id = 6;
          break;
        default:
          $id = 0;
          break;
      }        
      if ($v[2] >= 4) {
        $level = 'high';
      } elseif ($v[2] <= 1) {
        $level = 'low';
      } else {
        $level = 'medium';
      }
      $vSubskills[] = array('id'=>$id, 'level'=>$level);
    }
  }

  $cerProfile = array(
    'userid' => -1,
    'username' => $profile['username'],
    'firstname' => $profile['name'],
    'lastname' => '',
    'position' => $profile['department'],
    'profile' => array('skills' => $vSkills, STRING_SUBSKILLS => $vSubskills)
  );

  echo '<pre>'.print_r($cerProfile, true).'</pre>';
  return($profile);
}

?>