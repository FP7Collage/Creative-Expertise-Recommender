<?php
/**
 * Client functions
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, march 2014
 *
 * Public:
 *  getLista ($lista): el cliente puede obtener la $lista de skills o subskills
 *  showRecommendationTable($requirements, $listaSkills, $listaQualifications, $jrecommendation):
 *    displays the table of recommended candidates
 * Private:
 *  requestER ($url, $param): (interna) hace la request efectiva sobre la API de $url
 *  nombreSkill ($itemId, $names): traduce el $itemId a nombre segun $names
 *  searchItem ($array, $field, $value): searches $value in the field $field of the $array
 *  showProfile ($expert): displays the profile of that $expert
 * Internal:
 *  int2txt ($id, $lista): traduce el $id a texto segun $lista
 */
 
$urlER = 'http://www.htstats.com/collage/';
//$urlER = 'http://expertise.projectcollage.eu/';

require_once('version_database.php');
require_once('define_'.$versionDataBase.'.php');

// =============================================================================
function sendDataExample($url) {
  $postdata = json_encode(
      array(
          'username' => $_POST['username'],
          'password' => $_POST['password']
      )
  );

  $opts = array('http' =>
      array(
          'method'  => 'POST',
          'header'  => 'Content-type: application/x-www-form-urlencoded',
          'content' => $postdata
      )
  );
  $context  = stream_context_create($opts);
  $token = file_get_contents('http://esb.exactls.com/collage/cas/login', false, $context);
  return($token);
}

// =============================================================================
function requestER($url, $param = NULL, $decode = TRUE) {
/**
 * Makes a request to the API of ER service.
 * If $param, it makes a POST request.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 11.03.2014
 */
  global $urlER, $debugar, $token, $demoCER;
  
  // Check if the URL is internal or external
  $internal = TRUE;
  if (substr($url, 0, 4) == 'http') {
    $internal = FALSE;
  }
  
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
  
  //$token = readToken();
  $txtToken = '';
  if ($token != NULL) {
    if ($internal) {
      $txtToken = '&token='.$token;
    } else {
      $txtToken = '&ticket='.$token;
    }
  }
  
  if ($demoCER) {
    $url = $url.'&demo=1';
  }
  if ($internal) {
    $urlService = $urlER.$url.$txtToken;  // .'&debugar=1';
  } else {
    $urlService = $url.$txtToken;
  }
  if ($debugar) {
    //echo('<p>Downloading "'.$url.': '.$urlService.'", param='.$param.'</p>');
  }
  if ($debugar) echo ('requestER: final URL requested: '.$urlService. '<br />'."\n");
  $res = file_get_contents($urlService, false, $context);
  if ($debugar) echo ('requestER: response: '.$res. '<br />'."\n");
  if ($decode) {
    return(json_decode($res));
  } else {
    return($res);
  }
}  // END function requestER

// =============================================================================
function getLista ($lista, $numItems = 0) {
/**
 * Consulta al servicio "er/$lista" la lista de $lista (skills o subskills)
 * Devuelve el codigo html para escoger los elementos.
 * Si $numItems es 0, construye una unica lista de seleccion multiple.
 * Si no, construye $numItems listas desplegables llamadas $lista.$i.
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version march 2014
 */
  global $debugar, $token, $myInfo;
  
  if ($debugar) echo ('getLista: getting '.$numItems. ' items of type '.$lista.'<br />'."\n");
  $tlista = substr($lista, 0, strlen($lista) - 1);
  $elems = NULL;
  
  switch($lista) {
    case('mainskills'):
      if ($myInfo['infoNumber'] == VALID_CER_USER) {
        // OK, the user exists as a candidate in CER
        $txtImpExp = '
           <input type="radio" name="'.$lista.'" value="implicit" id="implicit_selection" onclick="showExplicitSkills(this.value);" checked="checked" /><label for="implicit_selection">Skills like mine</label>
           <input type="radio" name="'.$lista.'" value="explicit" id="explicit_selection" onclick="showExplicitSkills(this.value);" /><label for="explicit_selection">Let me choose specific skills</label>
        ';
      } else {
        // The user does not exists
        $txtImpExp = '
           <input type="radio" name="'.$lista.'" value="implicit" onclick="showExplicitSkills(this.value);" disabled="disabled" />Skills like mine <a href="info_profile" class="tooltip animate blue right" data-tool="This control is disabled because it needs to know your own skills and you haven\'t filled out your profile in Collage. Click here for more information.">(not available)</a>
           <input type="radio" name="'.$lista.'" value="explicit" onclick="showExplicitSkills(this.value);" checked="checked" />Let me choose specific skills
           <!-- p>Your profile is not stored in Collage. You can create it by following <a href="http://explicit.p2pum.imuresearch.eu?ticket='.$token.'">this link to User Profile Service</a>.</p-->
        ';
      }
      
      // Control for selecting explicit or implicit skills
      $content .= '
        <div id="'.$lista.'">
        <fieldset>
           <legend>Select how to define skills\' requirements:</legend>
           '.$txtImpExp.'
        </fieldset>
        </div><!-- END DIV '.$lista.' -->
      ';
    break;
    case('skills'):
      // List of skills
    case(STRING_SUBSKILLS):
      // List of subskills
      if ($elems == NULL) {
        $elems = requestER('er/'.$lista);
      }
      if ($debugar) echo ('getLista: elems downloaded: '.print_r($elems, TRUE).'<br />'."\n");
      if ($lista == 'skills') {
        $txtLegend = 'Select at least one required skill and its minimum desired level of expertise:';
      } else {
        $txtLegend = 'Select required '.STRING_SUBSKILL.':';
      }
      $txtMultiple = '';
      if ($numItems == 0) {
        $txtMultiple = ' multiple="multiple"';
        $nListas = 1;
      } else {
        $nListas = min($numItems, 10);
      }
      // Abrimos el div
      $content .= '
        <div id="'.$lista.'">
        <fieldset>
           <legend>'.$txtLegend.'</legend>
      ';
      // Escribimos las listas
      for ($i = 1; $i <= $nListas; $i++) {
        $content .= '
           <select name="'.$lista."[]".'"'.$txtMultiple.'>
        ';
        // Si tenemos varias listas, añadimos el elem "no skill/subskill"
        if ($nListas > 1) {
          $content .= '          <option value="0" selected="selected">-- no '.$tlista.' selected --</option>'."\n";
        }
        foreach ($elems as $elem) {
          $content .= '          <option value="'.$elem->id.'">'.$elem->text.'</option>'."\n";
        }
        $content .= '
            </select>
        ';
        // Si tenemos varias listas, tenemos soporte para niveles de cada expertise
        if ($nListas > 1 and $lista == 'skills') {
        $content .= '
          <select name="'.$lista.'_levels[]"'.$txtMultiple.'>
            <option value="none">None</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high" selected="selected">High</option>
          </select>
          <br />
        ';
        } else {
          $content .= '<br />';
        }
      }
      $content .= '
        </fieldset>
        </div><!-- END DIV '.$lista.' -->
      ';
    break;
    // =========================================================================
    case('proximity'):
      $content .= '
        <div id="'.$lista.'">
      ';
      if ($myInfo['infoNumber'] == VALID_CER_USER) {
        $txtAux = '
           <legend>Select '.$lista.' to '.int2dept($myInfo['infoDept']).':</legend>
           <input type="hidden" name="department" value="'.$myInfo['infoDept'].'" />
           <input type="radio" name="'.$lista.'" id="select_proximity_high" value="high" id="select_proximity_high"/><label for="select_proximity_high">High</label>
           <input type="radio" name="'.$lista.'" id="select_proximity_low" value="low" /><label for="select_proximity_low">Low</label>
           <input type="radio" name="'.$lista.'" id="select_proximity_disable" value="disable" checked="checked" /><label for="select_proximity_disable">Disable '.$lista.'</label>
        ';
      } else {
        $txtAux = '
           <legend>Select '.$lista.' <a href="info_profile" class="tooltip animate blue right" data-tool="This control is disabled because it needs to know your department and you haven\'t filled out your profile in Collage. Click here for more information.">(not available)</a>:</legend>
           <input type="radio" name="'.$lista.'" value="high" disabled="disabled" />High
           <input type="radio" name="'.$lista.'" value="low" disabled="disabled" />Low
           <input type="radio" name="'.$lista.'" value="disable" checked="checked" />Disable '.$lista.'
        ';
      }      
      $content .= '
        <fieldset>
           '.$txtAux.'
        </fieldset>
        </div><!-- END DIV '.$lista.' -->
      ';
    break;
    // =========================================================================
    case('expertise'):
      $content .= '
        <div id="'.$lista.'">
        <fieldset>
           <legend>Select expertise:</legend>
           <input type="radio" name='.$lista.'" value="similar" checked="checked" />Same as me
           <input type="radio" name='.$lista.'" value="different" />Different from me
           <input type="radio" name='.$lista.'" value="skills" />Choose skills...
        </fieldset>
        </div><!-- END DIV '.$lista.' -->
      ';
    break;
    default:
      return(NULL);
    break;
  }
  return(array($content, objectToArray($elems)));
}  // END functions getLista


function nombreSkill ($itemId, $names) {
  $nameOut = NULL;
  foreach($names as $name) {
    if ($name['id'] == $itemId) {
      $nameOut = $name['text'];
    }
  }
  if ($nameOut == NULL) {
    $nameOut = 'ID '.$itemId;
  }
  return($nameOut);
}

function searchItem ($array, $field, $value) {
/**
 * Searches $value in the field $field of the $array. If found, returns the
 * position. If not, returns -1.
 */
  //echo 'Buscando '.$value.' en el campo '.$field.' del array '.print_r($array, true).': ';
  $i = 0;
  $found = FALSE;
  while(!$found and $i < count(array_values($array))) {
    $aux = $array[$i];
    if ($aux[$field] == $value) {
      $found = TRUE;
    } else {
      $i = $i + 1;
    }
  }
  //echo ($found*1).'<br />';
  if ($found) {
    return ($i);
  } else {
    return (-1);
  }
}

// =============================================================================
function showProfile($expert, $req, $sysSkills, $sysQualif, $assessmentsAsOutput = FALSE) {
/**
 */
  global $debugar;
  //$profile = getCandidateProfile($userid);
  if ($debugar) {
    echo '<p>Expert:<pre>'.print_r($expert, TRUE).'</pre>';
  }
  $profile = $expert['profile'];
  //$content = '<br />Profile: <pre>'.print_r($profile, TRUE). '</pre>';
  
  $content .= '<ul>';
  
  // ===========================================================================
  // Skills
  $names = $sysSkills;
  $title = 'Required skills';
  $item  = 'skills';
  
  $content .= '<li><em>'.$title.'</em>: ';
  $c_skills = '';
  $i = 0;
  foreach ($profile[$item] as $skill) {
    $content_skill = '';
    $i = $i + 1;
    $requerida = FALSE;
    if (searchItem($req[$item], 'id', $skill['id']) >= 0) {
      $requerida = TRUE;
    }
    if ($requerida) {
      $content_skill .= '<strong>';
    }
    // Name of the skill
    $content_skill .= nombreSkill($skill['id'], $names);
    if ($requerida) {
      $content_skill .= '</strong>';
    }
    $content_skill .= ' ('.$skill['level'].')';
    // Si hay mas, ponemos una coma. Si no, un punto.
    //if ($i < count($profile[$item])) {
    //  $content_skill .= ', ';
    //} else {
    //  $content_skill .= '.';
    //}
    // Solo mostramos los requeridos, para evitar filas muy altas con perfiles largos
    if ($requerida) {
      if ($c_skills <> '') {
        $c_skills .= ', ';
      }
      $c_skills .= $content_skill;
    }
  }  // End foreach
  if ($c_skills == '') {
    $c_skills = 'none of the required';
  }
  $content .= $c_skills.'.';
  $content .= '</li>';
  
  // ===========================================================================
  // Subskills
  $names = $sysQualif;
  $title = may1a(STRING_SUBSKILLS);
  $item  = STRING_SUBSKILLS;
  
  $content .= '<li><em>'.$title.'</em>: ';
  $i = 0;
  foreach ($profile[$item] as $skill) {
    $i = $i + 1;
    $requerida = FALSE;
    //if (array_key_exists($item, $req)) {
    //  if (searchItem(array_values($req[$item]), 'id', $skill) >= 0) {
    //    $requerida = TRUE;
    //  }
    //}
    if (searchItem($req[$item], 'id', $skill['id']) >= 0) {
      $requerida = TRUE;
    }
    if ($requerida) {
      $content .= '<strong>';
    }
    // Name of the skill
    $content .= nombreSkill($skill['id'], $names);
    if ($requerida) {
      $content .= '</strong>';
    }
    // Si hay mas, ponemos una coma. Si no, un punto.
    if ($i < count($profile[$item])) {
      $content .= ', ';
    } else {
      $content .= '.';
    }
  }
  $content .= '</li>';
  
  // ===========================================================================
  // Availability
  $title = 'Availability';
  $item  = 'availability';
  $content .= '<li><em>'.$title.'</em>: ';
  $av = $profile[$item];
  switch ($av) {
    case (1):
      $txtAv = 'fully available';
    break;
    case (0):
      $txtAv = 'do not disturb';
    break;
    default:
      $txtAv = 'medium';
    break;
  }
  $content .= $txtAv. '</li>';

  // Assessments
  //if ($debugar) {
  if (!$assessmentsAsOutput) {
    // If $assessmentsAsOutput, avoid to output assessments in the profile
    $content .= '<li>Assessments (debug mode): <pre>'.print_r($expert['assessments'], TRUE).'</pre></li>';
  }
  //}
  
  $content .= '</ul>';
  if (!$assessmentsAsOutput) {
    // Just return html content
    return($content);
  } else {
    // Return both content and assessments
    return(array($content, $expert['assessments']));
  }
}

// =============================================================================
function int2txt($id, $lista) {
  $text = 'id_'.$id;
  foreach($lista as $item) {
    if ($item['id'] == $id) {
      return($item['text']);
    }
  }
  return($text);
}

// =============================================================================
function showRequirements($req, $listaSkills, $listaQualifications) {
  global $debugar;
  $content = '';
  
  $content .= '<h2>Requirements:</h2><ol start="0">';
  // Skills:
  if (count($req['skills']) > 0) {
    foreach ($req['skills'] as $item) {
      $content .= '<li>Skill "'.int2txt($item['id'], $listaSkills).'" ('.$item['level'].')</li>';
    }
  }
  // Qualifications:
  if (count($req[STRING_SUBSKILLS]) > 0) {
    foreach ($req[STRING_SUBSKILLS] as $item) {
      $content .= '<li>'.STRING_SUBSKILL.' "'.int2txt($item['id'], $listaQualifications).'"</li>';
    }
  }
  // Proximity:
  if ($req['proximity']['level'] != 'disable') {
    $content .= '<li>'.may1a($req['proximity']['level']).' proximity</li>';
  }
  // Availability:
  $content .= '<li>'.may1a($req['availability']['level']).' availability</li>';
  
  $content .= '</ol>'."\n";
  
  return ($content);
}

// =============================================================================
function showRecommendationTable($requirements, $listaSkills, $listaQualifications, $jrecommendation, $showAssessments = FALSE) {
/**
 * Genera una tabla con length(requirements) filas y length(recommendation) columnas
 */
  global $moodle, $courseid;
  
  // Retrieve list of skills and qualifications
  $sysSkills = objectToArray(requestER('er/skills'));
  $sysQualif = objectToArray(requestER('er/'.STRING_SUBSKILLS));

  $content = '';
  //echo '<pre>'.print_r($requirements, TRUE).'</pre>';
  $rec = objectToArray(json_decode($jrecommendation));
  //echo '<pre>'.print_r($rec, TRUE).'</pre>';
  
  // Generamos la cabecera en funcion de los requirements
  $content .= '<p>These are the candidates recommended by CER according to your requirements. A <span class="bueno">dark-coloured</span> cell indicates the maximum agreement of the candidate\'s profile to each individual requirement, while <span class="nulo">a light colour</span> states for non-covered requirements.</p>';
  if ($moodle) {
    // Span for showing the messages from moodle.php when enroling users
    $content .= '
    <div id="mensaje">
    </div>
    '; 
  }
  $content .= '<table class="recommendation"><thead><tr><th rowspan="2">Candidate</th>';

  $content .= '<th colspan="'.count($requirements['skills']).'">Skills</th>';
  $contentRow2 = '';
  foreach ($requirements['skills'] as $item) {
    $contentRow2 .= '<th>'.may1a(int2txt($item['id'], $listaSkills)).'<br />('.$item['level'].')</th>'."\n";
  }

  if (count($requirements[STRING_SUBSKILLS]) > 0) {
    $content .= '<th colspan="'.count($requirements[STRING_SUBSKILLS]).'">'.may1a(STRING_SUBSKILLS).'</th>';
    foreach ($requirements[STRING_SUBSKILLS] as $item) {
      $contentRow2 .= '<th>'.may1a(int2txt($item['id'], $listaQualifications)).'</th>'."\n";
    }
  }
  
  if ($requirements['proximity']['level'] != 'disable') {
    // Proximity a que department?
    $content .= '<th>Proximity to '.$requirements['proximity']['id'].'</th>';
    $contentRow2 .= '<th>'.may1a($requirements['proximity']['level']).'</th>'."\n";
  }
  $content .= '<th>Availability</th>'."\n";
  $contentRow2 .= '<th>'.may1a($requirements['availability']['level']).'</th>'."\n";
  
  //$content .= '<th rowspan="2">Adequacy<br />Degree</th></tr><tr>'.$contentRow2;
  $content .= '<th rowspan="2">Relevance</th></tr><tr>'.$contentRow2;
  $content .= '</tr></thead>';
  
  // Recorremos las recomendaciones y las escribimos en forma de fila
  $content .= '<tbody>';
  $i = 1;
  $lastAssess = -1;
  foreach ($rec as $expert) {
    //echo 'Expert '.$i.': <pre>'.print_r($expert, TRUE).'</pre>';
    if ($i % 2 == 0) {
      $content .= '<tr class="par">';
    } else {
      $content .= '<tr class="impar">';
    }
    if ($lastAssess == $expert['assess']) {
      // Mantenemos el numero
      $ii = $iiAnt;
    } else {
      $ii = $i;
      $iiAnt = $ii;
      $lastAssess = $expert['assess'];
    }
    $txtClass = '';
    if ($i == count($rec)) {
      //$txtClass = ' class="clear"';
    }
    $content .= '  <td class="candidate"><strong>'.$iiAnt.'. '.$expert['firstname'].' '.$expert['lastname'].'</strong> ('.int2dept($expert['profile']['department']).')';
    if ($moodle) {
      require_once('moodle_functions.php');
      // Obtenemos el ID Moodle del candidato
      $userid_moodle = Moodle_GetUserId($expert['username']);
      if ($userid_moodle != NULL) {
        // Comprobamos si el candidato esta ya enrolado
        $enroled = Moodle_CheckEnrolment($userid_moodle, $courseid);
        if (!$enroled) {
          // Enrol button
          //$content .= ' (<a href="#" title="Enrol candidate to course '.$courseid.'">enrol</a>)'."\n";
          $content .= ' <input type="button" id="btnEnrolment'.$expert['userid'].'" href="javascript:;" onclick="Moodle_enrolUser('.$userid_moodle.', '.$courseid.');return false;" value="Enrol" />'."\n";
        } else {
          // Unenrol button
          $content .= ' <input type="button" id="btnEnrolment'.$expert['userid'].'" href="javascript:;" onclick="Moodle_unenrolUser('.$userid_moodle.', '.$courseid.');return false;" value="Unenrol" />'."\n";
        }
      }
    }
    $content .= '     <br />&nbsp;&nbsp;&nbsp;&nbsp;'.$expert['email'].''."\n";
    list($contentAux, $assessments) = showProfile($expert, $requirements, $sysSkills, $sysQualif, TRUE);
    $content .= $contentAux.'</td>'."\n";
    foreach ($assessments as $ass) {
      if ($ass == 1) {$clase = 'bueno'; $txt = 'The candidate totally fulfills this requirement';} else {
        if ($ass >= 0.666) {$clase = 'regular'; $txt = 'The candidate partially fulfills this requirement';} else {
          if ($ass >= 0.333) {$clase = 'malo'; $txt = 'The candidate slightly fulfills this requirement';} else {
            $clase = 'nulo'; $txt = 'The candidate does not fulfill this requirement';
          }
        }
      }
      $txtA = '<a href="" class="tooltip '.$clase.' animate recommendationCell" data-tool="'.$txt.'">';
      if ($showAssessments) {
        $txtCell = $txtA.round($ass, 3).'</a>';
      } else {
        $txtCell = $txtA.'</a>';
      }
      $content .= '<td class="assessment '.$clase.'">'.$txtCell.'</td>'."\n";
    }  # End foreach
    $content .= '<td class="assessment"><strong>'.round($expert['assess'], 3).'</strong></td>'."\n";
    
    // Mostramos sus habilidades
    //$content .= '  Skills:';
    $content .= '  </tr>'."\n";
    $i = $i + 1;
  }
  $content .= '</tbody></table>';
  
  return ($content);
}

// =============================================================================
function showRecommendations($jrec, $req) {
  global $debugar;
  $content = '';
  $rec = objectToArray(json_decode($jrec));

  // Retrieve list of skills and qualifications
  $sysSkills = objectToArray(requestER('er/skills'));
  $sysQualif = objectToArray(requestER('er/'.STRING_SUBSKILLS));

  // Analyse the recommendations
  $content .= '<h2>Recommended experts:</h2>';
  // RAW
  if ($debugar) {
    $content .= '<p>Raw recommendation:<pre>'.print_r($rec, TRUE).'</pre>';
  }
  // HTML
  $i = 1;
  $content .= '<ol id="recommendations">';
  $lastAssess = -1;
  foreach ($rec as $expert) {
    if ($lastAssess == $expert['assess']) {
      // Mantenemos el numero
      $ii = $iiAnt;
    } else {
      $ii = $i;
      $iiAnt = $ii;
      $lastAssess = $expert['assess'];
    }
    $txtClass = '';
    if ($i == count($rec)) {
      //$txtClass = ' class="clear"';
    }
    $content .= '  <li class="candidate" value="'.$iiAnt.'"'.$txtClass.'><strong>'.$expert['firstname'].' '.$expert['lastname'].'</strong> ('.$expert['department'].')<br />Assessment: '.$expert['assess'].'.';
    $content .= showProfile($expert, $req, $sysSkills, $sysQualif);
    // Mostramos sus habilidades
    //$content .= '  Skills:';
    $content .= '  </li>'."\n";
    $i = $i + 1;
  }
  $content .= '</ol>';

  if (FALSE) {  
    // Test: show the objects
    $content .= '<div class="debugInfo"><h2>Debug information:</h2>';
    //$content .= '<h3>Skills list</h3><pre>'.print_r($sysSkills, TRUE). '</pre>';
    //$content .= '<h3>Qualifications list</h3><pre>'.print_r($sysQualif, TRUE). '</pre>';
    $content .= '<h3 class="float">Raw requirements</h3><pre>'.print_r($req, TRUE). '</pre>';
    $content .= '<h3 class="float">Raw recommendation</h3><pre>'.print_r($rec, TRUE). '</pre>';
    $content .= '</div>';
  }
  return ($content);
}

?>