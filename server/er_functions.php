<?php
/**
 * ER functions
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, march 2014
 *
 * Public:
 *  getSkills(): devuelve la lista de skills, consultandola de la bbdd.
 *  getSubskills(): devuelve la lista de subskills, consultandola de la bbdd.
 *  getRecommendation(): devuelve las recomendaciones segun la request de entrada.
 *  getTranslation(): traduce la request de entrada a requirements. Uses
 *    interaction::json_translateRequestToRequirements().
 */

require_once('define_'.$versionDataBase.'.php');

// =============================================================================
function getSkills($json = TRUE) {
/**
 * Gets the list of available skills for expertise
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version march 2014
 */
  global $conn, $debugar;
  
	$sql = 'SELECT * FROM '.TABLE_SKILLS.' ORDER BY lower(text) ASC';
	$result = consulta($sql, $conn);
	
	$out = array();
	while ($obj = pg_fetch_object ($result)) {
	  $out[] = $obj;
    if ($debugar) {
      echo('Skill '.$obj->text)."\n";
    }
	}
	  
  if (FALSE) {
    # hand-build
    // Id, name
    $out = array();
    $out[] = array('id' => 1, 'text' => 'marketing');
    $out[] = array('id' => 2, 'text' => 'brainstorming');
    $out[] = array('id' => 3, 'text' => 'presentation');
    $out[] = array('id' => 4, 'text' => 'research/writing');
    $out[] = array('id' => 5, 'text' => 'conceptual design');
  }
  if ($json) {
    $out = json_encode($out);
  }
  return($out);
}

// =============================================================================
function getSubskills($json = TRUE) {
/**
 * Gets the list of available subskills
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version march 2014
 */
  global $conn;

	$sql = 'SELECT * FROM '.TABLE_SUBSKILLS.' ORDER BY lower(text) ASC';
	$result = consulta($sql, $conn);
	
	$out = array();
	while ($obj = pg_fetch_object ($result)) {
	  $out[] = $obj;
	}
	  
  if (FALSE) {
    # hand-build

    $out = array();
    $out[] = array('id' => 1, 'text' => 'quick response');
    $out[] = array('id' => 2, 'text' => 'prolific');
    $out[] = array('id' => 3, 'text' => 'extensive');
  }
  if ($json) {
    $out = json_encode($out);
  }
  return($out);
}

// =============================================================================
function getRecommendation($json = TRUE) {
/**
 * Gets the recommendation
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version march 2014
 */
  global $debugar;
  
  $reqs = objectToArray(json_decode(file_get_contents("php://input")));
  if ($debugar) {
    echo('<p>Recommender starts. Requirements received:</p>');
    echo'<pre>';
    var_dump($reqs);
    echo'</pre>';
  }
  
  // Check inputs and return error if so
  
  // Start recommendation
  
  // 1.- Identification module: list of initial candidates
  $initialCandidates = getFeasibleCandidates($reqs);
  if ($debugar)
    echo '<h2>Initial candidates:</h2><pre>'. print_r($initialCandidates, TRUE).'</pre>';
  
  // 2.- Selection module: assess candidates and rank them
  $finalCandidates = assessAndRank($initialCandidates, $reqs);
  
  if ($debugar)
    echo '<h2>Final candidates:</h2><pre>'. print_r($finalCandidates, TRUE).'</pre>';
  
  if ($json) {
    $output = json_encode($finalCandidates);
  } else {
    $output = $finalCandidates;
  }
  return($output);
}

// =============================================================================
function getTranslation($json = TRUE) {
/**
 * Gets the recommendation
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version april 2014
 */
  global $debugar, $ticket;

  $jrequest = (file_get_contents("php://input"));
  
  // TODO: capturar el id del usuario segun el ticket
  //$userCred = getUserCredentials($ticket);
  //echo 'userCred: '.print_r($userCred). '<br />';
  $userid = 1;

  $requirements = json_translateRequestToRequirements($jrequest, $userid);
  $jrequirements = json_encode($requirements);
  
  return($jrequirements);
}
?>