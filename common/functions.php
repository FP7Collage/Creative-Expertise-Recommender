<?php
/**
 * Generic functions
 *
 * @author  Germán Sánchez (GREC-ESADE), Collage
 * @version 0.1, february 2015
 *
 * Public:
 *   ldebug($message, $reqlevel)
 *   getUserCredentials($token)
 *   checkTokenESB($token)
 *   isAdmin($credentials)
 *   isUser($credentials)
 *   int2dept($dept)
 *   may1a($texto)
 *   readToken()
 *   getUser()
 *   multi_sort($array, $akey)
 *   objectToArray($d)
 *   arrayToObject($d)
 */

if (!function_exists('ldebug')) {
# ==============================================================================
function ldebug ($message, $reqlevel) {
# Prints the message (echo <p>$message</p>) if the current $debuglevel (global)
# is greater than the required level $reqlevel
# ==============================================================================
  global $debuglevel;
  if ($debuglevel >= $reqlevel) {
    echo '<p class="debug" level="$reqlevel">Debug: '.$message.'</p>'."\n";
  }
}
}
 
// =============================================================================
function getUserCredentials($token) {
  global $debugar;
  
  $urlESB = 'http://esb.exactls.com/collage/cas/user?ticket='.$token;
  if ($debugar) echo ('getUserCredentials: downloading credentials from '.$urlESB.'...<br />'."\n");
  $credentials = json_decode(
    @file_get_contents($urlESB),
    true
  );
  return($credentials);
}

// =============================================================================
function checkTokenESB($token) {
  global $debugar;

  if ($debugar) echo ('checkTokenESB(): checking token "'.$token.'".<br />'."\n");
  
  if (!isset($token)) {
    if ($debugar) echo ('checkTokenESB(): KO, empty token.<br />'."\n");
    $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null ;
  }
  if ($token == NULL) {
    if ($debugar) echo ('checkTokenESB(): KO, null token.<br />'."\n");
    $output[] = array('errorNumber' => 1, 'errorText' => 'Identification token is required');
    echo json_encode($output);
    die();
  } else {
    // Check credentials in EBS
    // http://esb.exactls.com/collage/cas/user?token=ST-114-vC2Gb1k1vDGgxlkoefFm-cas01.example.org
    // If the token is invalid, file_get_contents raises an error.
    $credentials = getUserCredentials($token);
    if ($debugar) echo ('checkTokenESB(): downloaded credentials: "'.print_r($credentials, TRUE).'".<br />'."\n");
    return($credentials);
  }
}

// =============================================================================
function isAdmin($credentials) {
  global $debugar;
  if ($debugar) echo ('isAdmin(): checking downloaded credentials...'."\n");
  $role = $credentials['role'];
  //print_r($credentials);
  $posUser = strpos(strtolower($role), 'admin');
  if ($posUser === false) {
    if ($debugar) echo ('isAdmin(): KO, invalid role"'.$role.'"! Response:'."\n");
    $output[] = array('errorNumber' => 3, 'errorText' => 'User has no admin privileges');
    //echo json_encode($output);
    return(false);
  } else {
    if ($debugar) echo ('isAdmin(): Ok, user '.$credentials['username'].' authenticated as "'.$role.'"!<br />'."\n");
    return(true);
  }
}

// =============================================================================
function isUser($credentials) {
// Checks if the user is a user of the system
// =============================================================================
  global $debugar;
  if (isAdmin($credentials)) {
    return(true);
  } else {
    if ($debugar) echo ('isUser(): checking downloaded credentials...'."\n");
    $role = $credentials['role'];
  
    $posUser = strpos(strtolower($role), 'user');
    //if (in_array($role, array('admin', 'user'))) {
    if ($posUser === false) {
      if ($debugar) echo ('isUser(): KO, invalid credentials "'.$role.'"! Response:'."\n");
      //$output[] = array('errorNumber' => 2, 'errorText' => 'User has not role "user" in his/her credentials ('.json_encode($credentials).')');
      $output[] = array('errorNumber' => 2, 'errorText' => 'Invalid credentials of the user');
      echo json_encode($output);
      return(false);
    } else {
      if ($debugar) echo ('isUser(): Ok, user '.$credentials['username'].' authenticated as "'.$role.'"!<br />'."\n");
      return(true);
    }
    return (false);
  }
}

// =============================================================================
function int2dept($dept) {
  global $versionDataBase;
/**
 * Translates the ID of the department to text
 */
  if ($versionDataBase == 'waag') {
    $departments = array(1 => 'Waag', 2 => 'City');
    $department = $departments[$dept];
    if ($department == null) {
      $department = 'Unknown';
    }
  } else {
    $department = $dept;
    if ($department == '') {
      $department = 'Unknown';
    }
  }
  return ($department);
}

// =============================================================================
function may1a($texto) {
/**
 * Capitalises just the initial letter of the word
 */
  $texto[0] = strtoupper($texto[0]);
  return ($texto."");
}

// =============================================================================
function readToken() {
/**
 * Reads token received from CAS. If so, saves it to a cookie. If not, tries to
 * load it from the cookie.
 * German, 10.03.2014
 * Collage
 */
  global $webService, $debugar;
  if (!isset($webService)) {
    $webService = TRUE;
  }
  if (!isset($debugar)) {
    $debugar = false;
  }
  if(isset($_REQUEST['ticket']) | isset($_REQUEST['token'])) {
    // Received token.
    if(isset($_REQUEST['ticket'])) {
      $token = $_REQUEST['ticket'];
    } else {
      $token = $_REQUEST['token'];
    }
    if (!$webService) {
      // Save token in a cookie
      echo 'Saved token '.$token.' to a cookie.';
      setcookie('myToken', $token, time()+60*60*48);
    }
    return($token);
  } else {
    if (!$webService) {
      // Try to load token
      if (isset($_COOKIE['myToken'])) {
        if ($debugar) {
          echo 'Loaded token '.$token.' from a cookie.';
        }
        $token = $_COOKIE['myToken'];
        return($token);
      }
    }
	}
	// By default, return null
	return(NULL);
}

// =============================================================================
function checkToken_OLD() {
  if(isset($_REQUEST['ticket']) | isset($_REQUEST['token'])) {
    // Received token.
    if(isset($_REQUEST['ticket'])) {
      $token = $_REQUEST['ticket'];
    } else {
      $token = $_REQUEST['token'];
    }
    return($token);
  } else {
    header("Location: login.php");
  }
}

// =============================================================================
function getUser() {
  global $utilizarCAS, $useCAS, $demoCER;
  
  if (!isset($utilizarCAS)) {
    $utilizarCAS = TRUE;
  }
  if ($utilizarCAS & $useCAS & !$demoCER) {
    return(phpCAS::getUser());
  } else {
    return('Demo User');
  }
}

// =============================================================================
function multi_sort($array, $akey) { 
  function compare($a, $b) {
     global $key;
     return strcmp($a[$key], $b[$key]);
     //return ($a[$key] > $b[$key]);
  }
  usort($array, "compare");
  return $array;
}

// =============================================================================
function array_key_multi_sort($arr, $l , $f='>')
{
  if ($f == '>') {
    usort($arr, create_function('$a, $b', "return (\$b['$l'] > \$a['$l']);"));
  } else {
    // For string comparison, using $f (for instance, "strnatcasecmp")
    usort($arr, create_function('$a, $b', "return $f(\$b['$l'], \$a['$l']);"));
  }
  return($arr);
}

// =============================================================================
function peasoSort($results, $campo) {
  function custom_sort($a, $b) {
    global $campo;
    //return $a[$campo] > $b[$campo];
    return strcmp($a[$campo], $b[$campo]);
 }
 usort($results, "custom_sort");
 return $results;
}

// =============================================================================
function objectToArray($d) {
  if (is_object($d)) {
    // Gets the properties of the given object
    // with get_object_vars function
    $d = get_object_vars($d);
  }

  if (is_array($d)) {
    /*
    * Return array converted to object
    * Using __FUNCTION__ (Magic constant)
    * for recursive call
    */
    return array_map(__FUNCTION__, $d);
  }
  else {
    // Return array
    return $d;
  }
}
// =============================================================================
function arrayToObject($d) {
  if (is_array($d)) {
    /*
    * Return array converted to object
    * Using __FUNCTION__ (Magic constant)
    * for recursive call
    */
    return (object) array_map(__FUNCTION__, $d);
  }
  else {
    // Return object
    return $d;
  }
}	

if (!function_exists('array_column')) {
    /**
     * https://github.com/ramsey/array_column/blob/master/src/array_column.php
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();
        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }
        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
                E_USER_WARNING
            );
            return null;
        }
        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }
        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;
        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }
        $resultArray = array();
        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;
            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }
            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }
            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }
        }
        return $resultArray;
    }
}
?>