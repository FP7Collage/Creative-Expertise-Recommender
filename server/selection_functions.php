<?php
/**
 * SELECTION functions
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, march 2014
 */

/*
function assessSkill($profile, $req)
function assessSkill_old($profile, $req)
function assessQualification($profile, $req)
function assessProximity($profile, $req)
function assessAvailability($profile, $req)
function aggregateValues($values, $owaWeights)
function quantifier($r)
function computeWeight ($i, $n)
function getOWAWeights ($n)
function assessAndRank($initialCandidates, $reqs)
 */

require_once('define_'.$versionDataBase.'.php');

// =============================================================================
function assessSkill($profile, $req) {
/**
 * Computes the assessment of the requirement $req (array of 'id' & 'level')
 * according to the $profile.
 */
  global $debugar;
  
  // Default value if the requirement is not found in the $profile:
  if ($req['level'] == 'none') {
    // If the user does not have the required skill, and requirement is 0, 1.
    $assessment = 1;  // Req none, Ski none
  } else {
    $assessment = 0;  // Req >none, Ski none
  }

  // Search for the required skill in the profile  
  $candSkills = $profile['skills'];  // Array of 'id'+'level'
  //echo 'AQUII profile:<pre>'.print_r($profile, true).'</pre>';
  foreach($candSkills as $candSkill) {
    if ($candSkill['id'] == $req['id']) {
      // The candidate has the required skill!
      switch($req['level']) {
        case('high'):
          switch($candSkill['level']) {
            case('high'):
              $assessment = 1;  // Req H, Ski H
            break;
            case('medium'):
              $assessment = 2/3;  // Req H, Ski M
            break;
            case('low'):
              $assessment = 1/3;  // Req H, Ski L
            break;
          }
        break;
        case('medium'):
          switch($candSkill['level']) {
            case('high'):
            case('medium'):
              $assessment = 1;  // Req M, Ski H|M
            break;
            case('low'):
              $assessment = 1/2;  // Req M, Ski L
            break;
          }
        break;
        case('low'):
          $assessment = 1;  // Req L, Ski H|M|L
        break;
        case('none'):  // The user requires not to have the skill
          switch($candSkill['level']) {
            case('high'):
              $assessment = 0;  // Req 0, Ski H
            break;
            case('medium'):
              $assessment = 1/3;  // Req 0, Ski M
            break;
            case('low'):
              $assessment = 2/3;  // Req 0, Ski L
            break;
          }
        break;
      }
      // If required skill is found among the user skills, compute assessment and exit
      break;
    }
  }
  return ($assessment);
}

// =============================================================================
function assessSkill_old($profile, $req) {
/**
 * $req is an array of 'id' & 'level'
 */
  $candSkills = $profile['skills'];  // Array of 'id'+'level'
  foreach($candSkills as $candSkill) {
    $assessment = 0;  // If the user does not has the required skill, 0.
    if ($candSkill['id'] == $req['id']) {
      // The candidate has the required skill!
      switch($req['level']) {
        case('high'):
          switch($candSkill['level']) {
            case('high'):
              $assessment = 1;
            break;
            case('medium'):
              $assessment = 2/3;
            break;
            case('low'):
              $assessment = 1/3;
            break;
          }
        break;
        case('medium'):
          switch($candSkill['level']) {
            case('high'):
            case('low'):
              $assessment = 1/2;
            break;
            case('medium'):
              $assessment = 1;
            break;
          }
        break;
        case('low'):
          switch($candSkill['level']) {
            case('high'):
              $assessment = 1/3;
            break;
            case('medium'):
              $assessment = 2/3;
            break;
            case('low'):
              $assessment = 1;
            break;
          }
        break;
      }
      // If required skill is found among the user skills, compute assessment and exit
      break;
    }
  }
  //// If the required skill is among the candidate skills
  //if (in_array($req['id'], $candSkills)) {
  //  $assessment = 1;
  //} else {
  //  $assessment = 0;
  //}
  return ($assessment);
}
// =============================================================================
function assessQualification($profile, $req) {
/**
 * Computes the assessment of the requirement $req (array of 'id')
 * according to the $profile.
 */
  $candSkills = $profile[STRING_SUBSKILLS];  // Array of ids
  // If the required skill is among the candidate skills
  foreach($candSkills as $candSkill) {
    if ($candSkill['id'] == $req['id']) {
      // The candidate has the required skill!
      $assessment = 1;
    } else {
      $assessment = 0;
    }
  }
  return ($assessment);
}
// =============================================================================
function assessProximity($profile, $req) {
/**
 * Returns -1 if proximity is disabled in the requirements
 * Returns 1 if the dept of the profile is equal to the required dept id
 * $req is an array of 'level'
 */
  if ($req['level'] <> 'disable') {
    if ($req['level'] == 'high') {
      if ($req['id'] == $profile['department'])
        $assessment = 1;
      else
        $assessment = 0;
    } else {
      if ($req['id'] == $profile['department'])
        $assessment = 0;
      else
        $assessment = 1;
    }
  } else {
    $assessment = -1;
  }
  return ($assessment);
}
// =============================================================================
function assessAvailability($profile, $req) {
/**
 * $req is an array of 'level'
 * TODO
 */
  $assessment = $profile['availability'];
  $assessment = min(max($assessment, 0), 1);
  return ($assessment);
}

// =============================================================================
function aggregateValues($values, $owaWeights) {
/**
 * TODO: aggregates the $values. The first version just makes the mean
 */
  // First option: mean
  //$agg = array_sum($values) / count($values);
  // Second option: OWA
  rsort($values);
  $agg = 0;
  for ($i = 0; $i < count($values); $i++) {
    $agg = $agg + $values[$i] * $owaWeights[$i];
  }
  return($agg);
}
// =============================================================================
function quantifier($r) {
/**
 * Linguistic quantifier "most of"
 * (Chiclana et al., 2007)
 */
  //return($r^(1/2));
  return(sqrt($r));
}
function computeWeight ($i, $n) {
/**
 * Computes the $i-th weight out of $n
 */
  return(quantifier($i/$n) - quantifier(($i-1)/$n));
}
function getOWAWeights ($n) {
/**
 * Computes $n weights using linguistic quantifier most of
 */
  $weights = array();
  for ($i = 1; $i <= $n; $i++) {
    $weights[] = computeWeight($i, $n);
  }
  return($weights);
}
// =============================================================================

// =============================================================================
function assessAndRank($initialCandidates, $reqs) {
/**
 * Core function of the system. Assessess and ranks the initial candidates
 * according to the requirements.
 */
  global $debugar, $token;
  $owaWeights = NULL;
	$out = array();
	//echo 'REQS: <pre>'.print_r($reqs, TRUE).'</pre>';
  foreach ($initialCandidates as $cand) {
    //echo 'AQUII cand:<pre>'.print_r($cand, true).'</pre>';
    $profile = $cand['profile'];
    $assessments = array();
    // Skills
    foreach ($reqs['skills'] as $reqSkill) {
      $assessment = assessSkill($profile, $reqSkill);
      $assessments[] = $assessment;
      if ($debugar)
        echo 'Candidate '.$cand['username'].'. Assessment for required skill '.$reqSkill['id'].'-'.$reqSkill['level'].': '.$assessment.'<br />';
    }
    // Subskills
    if (array_key_exists(STRING_SUBSKILLS, $reqs)) {
      foreach ($reqs[STRING_SUBSKILLS] as $reqSkill) {
        //echo '<pre>'.print_r($reqSkill).'</pre>';
        $assessment = assessQualification($profile, $reqSkill);
        $assessments[] = $assessment;
        if ($debugar)
          echo 'Candidate '.$cand['username'].'. Assessment for required subskill '.$reqSkill['id'].'-'.$reqSkill['level'].': '.$assessment.'<br />';
      }
    }
    
    // Proximity
    $assessment = assessProximity($profile, $reqs['proximity']);
    if ($assessment != -1) {
      $assessments[] = $assessment;
      if ($debugar)
        echo 'Candidate '.$cand['username'].'. Assessment for proximity: '.$assessment.'<br />';
    }
    // Availability
    $assessment = assessAvailability($profile, $reqs['availability']);
    $assessments[] = $assessment;
    if ($debugar)
      echo 'Candidate '.$cand['username'].'. Assessment for availability: '.$assessment.'<br />';
    
    if ($owaWeights == NULL) {
      $owaWeights = getOWAWeights(count($assessments));
      if ($debugar)
        echo 'OWA weights ('.count($assessments).'):<pre>'.print_r($owaWeights, TRUE).'</pre>';
    }
    
    if ($debugar)
      echo 'Assessments for candidate <strong>'.$cand['username'].'</strong>: '.print_r($assessments, TRUE).'<br />';
    $cand['assessments'] = $assessments;
    $cand['assess'] = round(aggregateValues($assessments, $owaWeights) * 10000, 0) / 10000;
    // Random assessment
    // $cand['assess'] = round(mt_rand() / mt_getrandmax() * 10000, 0) / 10000;
	  $out[] = $cand;
	}

  // Sorting by assessment
  $out = array_key_multi_sort($out, 'assess');

  return($out);
}

?>