<?php
// radio-platform.php - functions to access the radio platform data
require_once('../log.php');
require_once('../passwords.php');

$modelApiUrl = APPLICATIONROOTURL . "/model/";

class Model {

  // tries to fix bad callerIds, removing leading whitespace, '+' or '0'
  private static function clean_phone_id($caller_id) {
    // if the callerId contains at least one letter, it's a SIP address
    $ph=trim($caller_id);
    if (preg_match('/[[:alpha:]]/', $ph) === 1) {
      $ph = preg_replace('/^sip:/', '', $ph);
      $ph = preg_replace('/@.*$/', '', $ph);
    } else {
      $ph=preg_replace('/\s*$/','',$ph);
      $ph=preg_replace('/^\s*/','',$ph);
      $ph=preg_replace('/^\+/','',$ph);
      $ph=preg_replace('/^0*/','',$ph);
    }
    return $ph;
  }

  // returns true if both numbers match
  private static function phoneNumbersMatch($n1, $n2) {
    if ($n1 === $n2) return true;
    return self::clean_phone_id($n1) === self::clean_phone_id($n2);
  }


  private static function callApi($action, $params = null) {
    global $modelApiUrl;
    $url = $modelApiUrl . "?action=" . $action;

    if (substr($action,0,4)=="add_") {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_POST, true);      
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    } else {
      if ($params != null) {
        $url .= "&" . http_build_query($params);
      }
      $ch = curl_init($url);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
      Log::system("ERROR: call to radio platform resulted in HTTP error. Request: ".$url."Error: ".curl_error($ch));
      Log::system("result:");
      Log::system($result);
      curl_close($ch);
      return false;
    } else {
      if ($result == FALSE) {
        Log::system("ERROR: call to radio platform returned empty result. Request: ".$url);
        curl_close($ch);
        return false;
      }
    }
    curl_close($ch);    
    $result = json_decode($result, TRUE);
    if ($result['status'] != "OK") {
      Log::system("ERROR: call to model api resulted in error. Request: ".$url."\nParams: ".print_r($params,TRUE)."\nMessage:".$result['message']."\n");
      return false;
    }
    return $result['content'];
  }

  public static function languageCode($langId) {
    $all_languages = self::callApi("get_languages");
    foreach($all_languages as $language) {
      if ($language['id'] === $langId) {
        return $language['code'];
      }
    }
    return null;
  }
  
  public static function findCaller($callerId) {
    $all_users = self::callApi("get_users");
    foreach($all_users as $user) {
      if (self::phoneNumbersMatch($user['phone'], $callerId)) {
        return $user;
      }
    }
    return null;
  }


  public static function userUnconfirmedMeetings($userId) {
    $user = self::callApi("get_user",array("userId"=>$userId));
    $userParticipations = array(); // will be an array of [meetinUserId, meeting]
    $allUsers = self::callApi("get_users");
    $allMeetings = self::callApi("get_meetings");

    Log::system($user);

    foreach ($user['meetings'] as $userMeetingId) {
      foreach ($allMeetings as $meeting) {
        if ($meeting['id'] === $userMeetingId) {
          foreach($meeting['participants'] as $participant) {
            if ($participant['userId'] === $user['id'] and $participant['status'] === 'unknown') {
                array_push($userParticipations, array("participantId"=>$participant['participantId'],
                                                      "meeting"=>$meeting));

              break;
            }
          }
          break;
        }
      }
    }
    return $userParticipations;
  }

  public static function languageId($languageCode) {
    $all_languages = self::callApi("get_languages");
    foreach ($all_languages as $language) {
      if ($language['code'] === $languageCode) {
        return $language['id'];
      }
    }
    return null;
  }


  public static function findById($array, $id) {
    foreach($array as $element) {
      if ($element['id'] === $id) {
        return $element;
      }
    }
    return null;
  }

}

?>