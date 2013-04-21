<?php
require_once("../passwords.php");
require_once("../log.php");
require_once("../platform.php");
require_once("rb.php");

class Controller {

  public static function init() {
    global $mysql_db_server, $mysql_db_name, $mysql_db_login, $mysql_db_password;
 
    // initialisation of hardwired things in the database.
    try {
      R::setup("mysql:host=$mysql_db_server;dbname=$mysql_db_name",$mysql_db_login,$mysql_db_password);
      if (R::count("language") == 0) {
        $lang = R::dispense("language"); $lang->code="en"; R::store($lang);
        $lang = R::dispense("language"); $lang->code="fr"; R::store($lang);
        $lang = R::dispense("language"); $lang->code="bam"; R::store($lang);
        $lang = R::dispense("language"); $lang->code="bmq"; R::store($lang);
        $lang = R::dispense("language"); $lang->code="dts"; R::store($lang);
        self::clear_user_log();
      }
    } catch (Exception $e) {
      Log::system("Error initialising db: ");
      Log::system($e);
      return FALSE;
    }
    return TRUE;
  }

  public static function clear_user_log() {
    Log::clearUserLog();
    array_map( "unlink", glob(APPLICATIONMEDIAPATH . "/message-*.wav"));
    return array('action'=>'clear_user_log', 'status'=>'OK');
  }

  public static function clear_system_log() {
    if (Log::clearSystemLog()) {
      return array('action'=>'clear_system_log', 'status'=>'OK');
    } else {
      return array('action'=>'clear_system_log', 'status'=>'FAIL');
    }
  }

  public static function upload_wav($params)
  {
Log::system("upload wav");
Log::system($params);
    $save_folder = $params['upload_dir'];
    $uploader = $params['from']; // flash or form
    if(! file_exists($save_folder)) {
      if(! mkdir($save_folder)) {
        return array('action'=>'upload_wav', 'status'=>'FAIL', 'message'=>'failed to create save folder $save_folder');
      }
    }

    function valid_wav_file($file) {

      $handle = fopen($file, 'r');
      $header = fread($handle, 4);
      list($chunk_size) = array_values(unpack('V', fread($handle, 4)));
      $format = fread($handle, 4);
      fclose($handle);
      return $header == 'RIFF' && $format == 'WAVE' && $chunk_size == (filesize($file) - 8);
    }

    $uploadedFile = $_FILES["uploadfile"];
    if ($uploader == "flash") {
      // file is uploaded with Flash
      $tmp_name = $uploadedFile["tmp_name"]['filename'];
      $upload_name = $uploadedFile["name"]['filename'];
      $type = $uploadedFile["type"]['filename'];
    } else {
      // file is uploaded by file upload 
      $tmp_name = $uploadedFile["tmp_name"];
      $type = $uploadedFile["type"];

      // we need to rename the file to the same as with flash
      $upload_name = "audio_".$params['lang'].".wav";
    }

  Log::system("tmp_name: ".$tmp_name);
  Log::system("upload_name: ".$upload_name);
  Log::system("type: ".$type);


    $filename = "$save_folder/$upload_name";
    $saved = 0;

    if(($type == 'audio/x-wav' || $type=='audio/wav') && preg_match('/^[a-zA-Z0-9_\-]+\.wav$/', $upload_name) && valid_wav_file($tmp_name)) {
      Log::system("file is valid wav");
      $saved = move_uploaded_file($tmp_name, $filename) ? 1 : 0;
      if ($saved) {
        global $ivrPlatform;
        $sox = $ivrPlatform->sox();
        exec("$sox $filename -r 8k -b 8 -e a-law -c 1 $filename.wav", $output,$retval);
        if ($retval != 0) {
          Log::system("error converting audio: ".$retval); 
          Log::system("output:"); Log::system($output); 
          unlink($filename.".wav");
          $saved = 0;
        } else {
          Log::system("sox succeeded"); 
          Log::system("renaming $filename.wav to $filename.");           

          rename($filename.".wav",$filename);
        }
      } else {
        return array('action'=>'upload_wav', 'status'=>'FAIL', 'message'=>'failed to save uploaded file');
      }
    } else {
      return array('action'=>'upload_wav', 'status'=>'FAIL', 'message'=>'file is not wav (type: '.$type.', name: '.$upload_name.')');
    }

    if(isset($_POST['format']) and $_POST['format'] == 'json') {
      header('Content-type: application/json');
    }

    if ($saved) {
      return array('action'=>'upload_wav', 'status'=>'OK', 'message'=>'file saved at: '.dirname(__FILE__)."/$filename");
    } else {
      Log::system($_FILES);
      Log::system("tmp_name: $tmp_name, filename: $filename");
      return array('action'=>'upload_wav', 'status'=>'FAIL');
    }
  }


  public static function add_meeting($params)
  {
    // used parameters: title,location,start_datetime,end_datetime,include_user,include_language,creatorId

    $meeting = R::dispense("meeting");
    if ($params['title']==="") return array('status'=>'FAIL','message'=>'empty title');
    if ($params['location']==="") return array('status'=>'FAIL','message'=>'empty title');
    if ($params['creatorId']==="") return array('status'=>'FAIL','message'=>'empty creator');
    $meeting->import($params,"title,location");
    // from the POST we get dates as strings like "2011-07-12 11:48"
    // for now we're storing year, month, day, hour, minute
    // we might have to use a DateTime later, in which case we'll need the Optimizer (god forbid)

    try {
      $nbmatches = sscanf($params['start_datetime'],"%d/%d/%d %d:%d",$start_month,$start_day,$start_year,$start_hours,$start_minutes);
    } catch (Exception $e) {
      return array('status'=>'FAIL','message'=>'invalid start datetime');
    }
    try {
      $nbmatches = sscanf($params['end_datetime'],"%d/%d/%d %d:%d",$end_month,$end_day,$end_year,$end_hours,$end_minutes);
    } catch (Exception $e) {
        return array('status'=>'FAIL','message'=>'invalid end datetime');
    }

    if ($nbmatches != 5) 
      return array('status'=>'FAIL','message'=>'failed to parse datetime');

    $meeting->start_year=$start_year;
    $meeting->start_month=$start_month;
    $meeting->start_day=$start_day;
    $meeting->start_hours=$start_hours;
    $meeting->start_minutes=$start_minutes;

    $meeting->end_year=$end_year;
    $meeting->end_month=$end_month;
    $meeting->end_day=$end_day;
    $meeting->end_hours=$end_hours;
    $meeting->end_minutes=$end_minutes;

    $meeting->creatorId = intval($params['creatorId']);

    foreach($params['include_user'] as $userId) {
      $user=R::load("user",$userId);
      R::associate($meeting,$user,array("status"=>"unknown", "messageUrl"=>null, "nbTimesCalled"=>0));
    }

    foreach($params['include_language'] as $languageId) {
      $language=R::load("language",$languageId);
      R::associate($meeting,$language);

      // change the name of the  audio file uploaded by the flash into the right one
      $uploadedAudioFileName = APPLICATIONMEDIADIR . "/audio_" . $language->code . ".wav";
      Log::system("Copying $uploadedAudioFileName to ".self::meeting_audio_filename($meeting->id, $languageId));
      if (!copy($uploadedAudioFileName, self::meeting_audio_filename($meeting->id, $languageId)))
        return array('action'=>'add_meeting','status'=>'FAIL', 'message'=>'Failed to copy uploaded audio');
      if (!unlink($uploadedAudioFileName))
        return array('action'=>'add_meeting','status'=>'FAIL', 'message'=>'Failed to delete uploaded audio');
    }
    return array('action'=>'add_meeting','status'=>'OK','message'=>'meeting_added','content'=>self::meetingArray($meeting));
  }

  public static function add_meeting_user($params) {
    // used parameters, meeting_id, user_id
    if (!isset($params['meeting_id']) || !isset($params['user_id'])) {
      $result = array('status'=>'FAIL','message'=>'missing parameter in add_meeting_user');
    } else {
      $meeting_id = $params['meeting_id'];
      $user_id = $params['user_id'];
  
      $existing_relationships = R::find('meeting_user','user_id= :user_id and meeting_id= :meeting_id', array('user_id'=>$user_id, 'meeting_id'=>$meeting_id));
  
      if (count($existing_relationships) > 0) {
        $result = array('status'=>'FAIL','message'=>'relationship already existed for add_meeting_user (user: '.$user_id.', meeting: '.$meeting_id.'). No change made.');
      } else {
        $user = R::load("user",$params['user_id']);
        $meeting = R::load("meeting",$params['meeting_id']);
    
        if ($user==null || $meeting==null) {
          $result = array('status'=>'FAIL','message'=>'no meeting or user found for add_meeting_user');
        } else {
          $meeting_user = R::associate($meeting,$user,array("status"=>"unknown", "messageUrl"=>null, "nbTimesCalled"=>0));

          $result = array('status'=>'OK',
                          'action'=>'add_meeting_user',
                          'message' => 'meeting user added',
                          'content'=>self::meetingArray($meeting));
        }
      }
    }
    return $result;
  }

  public static function set_meeting_languages($params) {
    if (!isset($params['meeting_id']) || !isset($params['meeting_languages']))
      return array('status'=>'FAIL','message'=>'missing parameter in set_meeting_languages');
    $meetingID = $params['meeting_id'];
    $meetingLanguages = $params['meeting_languages'];

    $meeting = R::load("meeting", $meetingID);
    foreach ($meetingLanguages as $meetingLanguage) {
      $language = R::load("language",$meetingLanguage['value']);
      if ($meetingLanguage['checked'] == 'true') {
        R::associate($meeting,$language);
      } else {
        R::unassociate($meeting,$language);
      }
    }
    return array('action'=>'set_meeting_languages','status'=>'OK','message'=>'meeting languages set');    
  }

  public static function add_language_meeting($params) {
    // used parameters, meeting_id, language_id
    if (!isset($params['meeting_id']) || !isset($params['language_id']))
      return array('status'=>'FAIL','message'=>'missing parameter in add_language_meeting');

    $languageId = $params['language_id'];
    $meetingId = $params['meeting_id'];

    // we must check if that language already exists for that meeting

    $existing_relationships = R::find('language_meeting','language_id= :language_id and meeting_id= :meeting_id', array('language_id'=>$languageId, 'meeting_id'=>$meetingId));

    if (count($existing_relationships) > 0)
      return array('status'=>'FAIL','message'=>'relationship already exists for add_language_meeting (language: '.$languageId.', meeting: '.$meetingId.'). No change made.');

    $language = R::load("language",$languageId);
    $meeting = R::load("meeting",$meetingId);


    if ($language==null || $meeting==null) {
      return array('status'=>'FAIL','message'=>'no meeting or language found for add_language_meeting');
    } else {
      $language_meeting = R::associate($meeting,$language);
      return array('status'=>'OK','message'=>'language_meeting added','language_meeting_id'=>$language_meeting);
    }
  }


  public static function add_user($params) {
    // used parameters: name, phone, include_language
    $user = R::dispense("user");
    $user->import($params,"name,phone");
    if ($params['name'] === "") {
      return array('status'=>'FAIL','message'=>'contact_name_required');
    }
    if ($params['phone'] === "") {
      return array('status'=>'FAIL','message'=>'contact_phone_required');
    }
    if (!isset($params['include_language']) or count($params['include_language']) === 0) {
      return array('status'=>'FAIL','message'=>'contact_languages_required');
    }
    $languagePreference = 0;
    foreach($params['include_language'] as $languageId) {
      $language=R::load("language",$languageId);
      R::associate($user,$language,array("preference"=>$languagePreference++));
    }
    R::store($user);
    return array('status'=>'OK','message'=>'contact added');
  }

  public static function change_user($params) {
    $userId = $params['id'];
    $newValue = $params['value'];
    $fieldToChange = $params['field_to_change'];

    $user = R::findOne("user","id=".$userId);
    switch($fieldToChange) {
    case "name": $user->name = $newValue; break;
    case "phone": $user->phone = $newValue; break;
    default: return array('status'=>'FAIL', 'message'=>'unknown fieldToChange '.$fieldToChange.' in change_user '.$userId);
    }
    R::store($user);
    return array('status'=>'OK','message'=>'user modified','content'=>self::format_user($user));
  }

  public static function change_meeting($params) {
    $meetingId = $params['id'];
    $newValue = $params['value'];
    $fieldToChange = $params['field_to_change'];

    $meeting = R::findOne("meeting","id=".$meetingId);
    switch($fieldToChange) {
    case "title": $meeting->title = $newValue; break;
    case "creatorId": $meeting->creatorId = $newValue; break;
    case "location": $meeting->location = $newValue; break;
    case "start_datetime":
      $nbmatches = sscanf($newValue,"%d/%d/%d %d:%d",$month,$day,$year,$hours,$minutes);
      if ($nbmatches != 5) 
        return array('status'=>'FAIL','message'=>'failed to parse date');
      $meeting->start_year=$year;
      $meeting->start_month=$month;
      $meeting->start_day=$day;
      $meeting->start_hours=$hours;
      $meeting->start_minutes=$minutes;
      break;
    case "end_datetime":
      $nbmatches = sscanf($newValue,"%d/%d/%d %d:%d",$month,$day,$year,$hours,$minutes);
      if ($nbmatches != 5) 
        return array('status'=>'FAIL','message'=>'failed to parse date');
      $meeting->end_year=$year;
      $meeting->end_month=$month;
      $meeting->end_day=$day;
      $meeting->end_hours=$hours;
      $meeting->end_minutes=$minutes;
      break;
    default: return array('status'=>'FAIL', 'message'=>'unknown fieldToChange '.$fieldToChange.' in change_meeting '.$meetingId);
    }
    R::store($meeting);
    return array('status'=>'OK','message'=>'meeting modified','content'=>self::meetingArray($meeting));
  }
                 

  public static function delete_meeting($params) {
    // parameters: select_meeting
    $meetingIds = "(".$params['select_meeting'].")";
    $meetingsToDelete = R::find("meeting", "id in $meetingIds");
    if (count($meetingsToDelete) === 0) {
      Log::system("warning: trying to delete a non-existing meeting (ids: " . $meetingIds . ")");
    }
    foreach($meetingsToDelete as $meetingToDelete) {
      $meetingLanguages = R::find("language_meeting", $meetingToDelete->id . " = meeting_id");
      foreach($meetingLanguages as $meetingLanguage) {
        // remove the audio in that language of the meeting
        $audioFilename = self::meeting_audio_filename($meetingToDelete->id, $meetingLanguage->language_id);
        if (file_exists($audioFilename))
          unlink($audioFilename);
        else
            Log::system("warning: meeting audio file $audioFilename missing.");
      }
      R::clearRelations($meetingToDelete,'language');
      R::clearRelations($meetingToDelete,'user');
      R::trash($meetingToDelete);
    }
    return array('status'=>'OK', 'message'=>'meetings deleted');
  }

  public static function delete_user($params) {
    // params: select_user
    $userIds = "(".$params["select_user"].")";
    $usersToDelete = R::find("user", "id in $userIds");
    foreach($usersToDelete as $userToDelete) {
      R::clearRelations($userToDelete,'language');
      R::clearRelations($userToDelete,'meeting');
      R::trash($userToDelete);
    }
    return array('status'=>'OK', 'message'=>'users deleted');
  }

  public static function delete_meeting_user($params) {
    $meetingUserId = $params['id'];
    $meetingUserToDelete = R::load("meeting_user",$meetingUserId);
    if ($meetingUserToDelete->id != 0) {
      $user = R::load('user',$meetingUserToDelete->user_id);
      $meeting = R::load('meeting',$meetingUserToDelete->meeting_id);
      R::trash($meetingUserToDelete);
      return array('status'=>'OK', 
                   'action'=>'delete_meeting_user', 
                   'message'=>'meeting user '.$meetingUserId.' deleted',
                   'content'=>self::meetingArray($meeting));
    } else {
      return array('status'=>'FAIL','message'=>'meeting user '.$meetingUserId.' does not exist');
    }
  }

  public static function delete_language_meeting($params) {
    $meetingLanguageId = $params['id'];
    $meetingLanguageToDelete = R::load("language_meeting",$meetingLanguageId);
    if ($meetingLanguageToDelete != null) {
      R::trash($meetingLanguageToDelete);
      return array('status'=>'OK','message'=>'meeting language '.$meetingLanguageId.' deleted');
    } else {
      return array('status'=>'FAIL','message'=>'meeting language '.$meetingLanguageId.' does not exist');
    }
  }

  // ##################################################
  // gets call when VXML interpreter gets an answer for an outbound call
  // this allows us to know whether the user has answered the call

  // Voicemail is a problem here, as the phone will answer the call,
  // but the user won't get the message. However in this usecase, most people 
  // don't have voicemail

  public static function ping_user_answered($params) 
  {
    //    $meetingUserId = $params["meeting_user_id"];
    //    $userMeeting = R::load("meeting_user",$meetingUserId);
    //    $user = R::load("user",$userMeeting->user_id);
    //    $meeting = R::load("meeting",$userMeeting->meeting_id);
    //    Log::user($user->name." has picked up our call about meeting '".$meeting->title."'.");
    return array('action'=>'ping_user_answered','status'=>'OK');
  }

  // ##################################################
  // log when a user calls the system
  // this function is called by the VXML interpreter when it receives a call
  
  public static function register_incoming_call($params)
  {

  Log::system("API: register_incoming_call");

    $callerId = $params["callerid"];
    $protocol = $params["protocol"];
    $protocolUUI = $params["protocolUUI"];
    $protocolVersion = $params["protocolVersion"];
    $localURI = $params["localuri"];
    $messageFileName = $params["filename"]; // name of the audio file with the message


    Log::system("CallerID: $callerId");

    // first check if this is a phone number
    if (preg_match("/^\+?[0-9\- .]+/",$callerId) == 1) {
      Log::system("this is a phone number");
      $callerId = substr($callerId,-8,8); // we're only going to compare the last 8 digits, as different platforms/operators return different prefixes (+2231234, 002231234, 01234, etc.)
      // check if we know this number
      $users = R::find("user","1");
      foreach($users as $user) {
        if (substr($user->phone,-8,8) == $callerId) {
          Log::user($user->name." a appelé et a laissé <a href='$messageFileName' target='_blank'>un message</a>.<br/>");
          return;
        }
      }
      Log::user("Le numéro <a href='#' class='newPhoneNumber'>$callerId</a> a appelé et a laissé <a href='$messageFileName' target='_blank'>un message</a>.<br/>");
    } else {
      Log::system("this is not a phone number");
      $callerIdString = "";
      if ($callerId != "unknown" and $callerId != "Anonymous") {
        $callerIdString = " (numéro: <a href='#' class='newPhoneNumber'>$callerId</a>)" ;
      }
      Log::user("Une personne non indentifiée a appelé".$callerIdString." et a laissé <a href='$messageFileName' target='_blank'>un message</a>.<br/>");
      Log::system("Une personne non indentifiée a appelé".$callerIdString." et a laissé <a href='$messageFileName' target='_blank'>un message</a>.<br/>");
    }
  }

  // ##################################################
  // log when a user calls the system
  // this function is called by the VXML interpreter when it receives a call
  
  public static function record_message_from_outgoing_call($params)
  {
Log::system("record_message_from_outgoing_call");
    $meetingUserId = $params["meetingUserId"];
    $messageFileName = $params["filename"]; // name of the audio file with the message
Log::system($params);
    $userMeeting = R::findOne("meeting_user","id=$meetingUserId");
    $userMeeting->messageUrl = $messageFileName;
Log::system($userMeeting);
    R::store($userMeeting);
Log::system("done");
    return array('action'=>'record_message_from_outgoing_call', 'status'=>'OK');
  }

  // ##################################################

  public static function change_meeting_participation($params)
  {
    // a participant has accepted a meeting request, or the operator changed it by hand
    // Parameters: meeting_user_id and new_state

  Log::system("change_meeting_participation");
  
    $meetingUserId = $params["meeting_user_id"];
    $newState = $params["meeting_participation_status"];

  Log::system("newState: $newState");


    $response = array('action'=>'change_meeting_participation');
    if ($newState == "yes" || $newState == "no" || $newState == "unknown" || $newState == "maybe") {
      $userMeeting = R::findOne("meeting_user","id=$meetingUserId");
      $userMeeting->status = $newState;
      R::store($userMeeting);
      $response['status']='OK';
      $response['message']='Answer is '.$newState;
    } else {
      $response['status']='FAIL';
      $response['message']='No such status: '.$newState;
    }
    return $response;
  }

  //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
  // call a user and plays the meeting's message
  
  public static function request_participation($params)
  {
    $meetingUserId = $params["meeting_user_id"];

    Log::system("received request_participation for meeting_user $meetingUserId");

    // first, find the meeting_user data
    $userMeeting = R::findOne("meeting_user","id=".$meetingUserId);
    $user = R::findOne("user","id=".$userMeeting->user_id);
    $meeting = R::findOne("meeting","id=".$userMeeting->meeting_id);
    Log::system("Calling ".$user->name." about meeting '".$meeting->title."'.");

    // the language must be one among the meeting's that the user speaks
    $languagesMeeting = R::getAll("select language_id from language_meeting where meeting_id = " . $userMeeting->meeting_id . ";");
    $languagesUser = R::getAll("select language_id from language_user where user_id = " . $userMeeting->user_id . " order by preference;");

    // the list of languages spoken by the user and also used at the meeting, ordered by user preferred language
    $meetingUserLanguages = R::getAll("select lu.language_id, lu.preference from language_user lu inner join language_meeting lm on lu.user_id = ".$userMeeting->user_id." and lm.meeting_id = ".$userMeeting->meeting_id." and lu.language_id = lm.language_id order by lu.preference");

    if (count($meetingUserLanguages)==0) {
      $userLangId = $languagesUser[0]['language_id']; // just pick the user's first language
      Log::system("no intersection. Using: $userLangId");
    } else {
      $userLangId = $meetingUserLanguages[0]['language_id']; // pick the user's preferred language of the meeting
      Log::system("selecting user's preferred language for the meeting: $userLangId");
    }

    $userlang = R::findOne("language","id=".$userLangId);

    global $ivrPlatform;
    $success = $ivrPlatform->call(Array("phone"=>$user->phone,
                                        "lang"=>$userlang->code,
                                        "audio_recording_url"=>self::meeting_audio_url($meeting->id, $userlang->id),
                                        "meeting_user_id"=>$meetingUserId));
    if ($success) {
      $userMeeting->nbTimesCalled++;
      R::store($userMeeting);
      return array("status"=>"OK","message"=>"participation requested");
    } else {
      return array("status"=>"FAIL","message"=>"Voice platform error");
    }
  }

  public static function get_meetings()
  {
    $meetings_from_db = R::find("meeting","1");
    $meetings = array();
    foreach($meetings_from_db as $meeting) {
      array_push($meetings, self::meetingArray($meeting));
    }
    return array('action'=>'get_meetings', 'status'=>'OK', 'content'=>$meetings);
  }


  /* make an JSONifiable array from a user extracted from the bbc */
  private static function format_user($user_from_db) {
    $user_meetings=array();
    foreach(R::related($user_from_db,'meeting') as $meeting) {
      array_push($user_meetings, intval($meeting->id));
    }
    $user_languages=array();
    foreach(R::related($user_from_db,'language') as $language) {
      $languageUser = R::getRow("select * from language_user where language_id=$language->id and user_id=$user_from_db->id");
      $user_languages[$languageUser['language_id']] = isset($languageUser['preference']) ? $languageUser['preference'] : 0;
    }
    asort($user_languages);
    return array("id"=>intval($user_from_db->id),
                 "name"=>$user_from_db->name,
                 "phone"=>$user_from_db->phone,
                 "languages"=>array_keys($user_languages),
                 "meetings"=>$user_meetings);
  }
    

  public static function get_users() 
  {
    $users_from_db = R::find("user","1");
    $users=array();
    foreach($users_from_db as $user) {
      array_push($users, self::format_user($user));
    }
    return array('action'=>'get_users', 'status'=>'OK', 'content'=>$users);
  }

  public static function get_user($params) 
  {
    $userId = $params['userId'];
    $user_from_db = R::findOne("user","id=".$userId);
    if ($user_from_db) {
      return array('action'=>'get_users', 'status'=>'OK', 'content'=>self::format_user($user_from_db));
    } else {
      return array('action'=>'get_users', 'status'=>'FAIL', 'message'=>"no user with id $userId");
    }
  }
  
  public static function get_languages()
  {
    $languages_from_db = R::find("language","1");
    $languages=array();
    foreach($languages_from_db as $language) {
      array_push($languages, array("id"=>intval($language->id), "code"=>$language->code));
    }
    return array('action'=>'get_languages', 'status'=>'OK', 'content'=>$languages);
  }




  // ####### private methods ##########

  // create a meeting array (including participants and languages) from the DB
  private static function meetingArray($meetingFromDB) {
    $participants = array();
    $participantsComing = 0;
    $participantsNotComing = 0;
    $participantsMaybeComing = 0;
    $participantsUnknown = 0;
    foreach(R::related($meetingFromDB,'user') as $participant) {
      $meetingUser = R::getRow("select * from meeting_user where meeting_id=$meetingFromDB->id and user_id=$participant->id");
      array_push($participants, array("userId"=>intval($participant->id),
                                      "status"=>$meetingUser['status'],
                                      "nbTimesCalled"=>intval($meetingUser['nbTimesCalled']),
                                      "messageUrl"=>$meetingUser['messageUrl'],
                                      "participantId"=>intval($meetingUser['id'])));
      switch ($meetingUser['status']) {
      case "yes": $participantsComing++; break;
      case "no": $participantsNotComing++; break;
      case "maybe": $participantsMaybeComing++; break;
      case "unknown": $participantsUnknown++; break;
      default: return "ERROR! Participation status unknown";
      }

    }
    $languages=array();
    foreach(R::related($meetingFromDB,'language') as $language) {
      array_push($languages, array("id"=>intval($language->id), "audioUrl"=>self::meeting_audio_url($meetingFromDB->id, $language->id)));
    }

    return array("id"=>intval($meetingFromDB->id),
                 "title"=>$meetingFromDB->title,
                 "creatorId"=>$meetingFromDB->creatorId,
                 "location"=>$meetingFromDB->location,
                 "start_day"=>$meetingFromDB->start_day, "start_month"=>$meetingFromDB->start_month, "start_year"=>$meetingFromDB->start_year,
                 "start_minutes"=>$meetingFromDB->start_minutes, "start_hours"=>$meetingFromDB->start_hours,
                 "end_day"=>$meetingFromDB->end_day, "end_month"=>$meetingFromDB->end_month, "end_year"=>$meetingFromDB->end_year,
                 "end_minutes"=>$meetingFromDB->end_minutes, "end_hours"=>$meetingFromDB->end_hours,
                 "languages"=>$languages,
                 "nbParticipants"=>count($participants),
                 "nbParticipantsComing"=>$participantsComing,
                 "nbParticipantsNotComing"=>$participantsNotComing,
                 "nbParticipantsMaybeComing"=>$participantsMaybeComing,
                 "nbParticipantsUnknown"=>$participantsUnknown,
                 "participants"=>$participants);
  }

  private static function meeting_audio_filename($meetingId, $languageID) {
    return APPLICATIONMEDIADIR . "/meeting-".$meetingId."-".$languageID.".wav";
  }

  private static function meeting_audio_url($meetingId, $languageID) {
    return APPLICATIONROOTURL."/media/meeting-$meetingId-$languageID.wav";
  }

  private static function gzdecode($data,&$filename='',&$error='',$maxlength=null) 
  {
    $len = strlen($data);
    if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
        $error = "Not in GZIP format.";
        return null;  // Not GZIP format (See RFC 1952)
    }
    $method = ord(substr($data,2,1));  // Compression method
    $flags  = ord(substr($data,3,1));  // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    // NOTE: $mtime may be negative (PHP integer limitations)
    $mtime = unpack("V", substr($data,4,4));
    $mtime = $mtime[1];
    $xfl   = substr($data,8,1);
    $os    = substr($data,8,1);
    $headerlen = 10;
    $extralen  = 0;
    $extra     = "";
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false;  // invalid
        }
        $extralen = unpack("v",substr($data,8,2));
        $extralen = $extralen[1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false;  // invalid
        }
        $extra = substr($data,10,$extralen);
        $headerlen += 2 + $extralen;
    }
    $filenamelen = 0;
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos(substr($data,$headerlen),chr(0));
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr($data,$headerlen,$filenamelen);
        $headerlen += $filenamelen + 1;
    }
    $commentlen = 0;
    $comment = "";
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false;    // invalid
        }
        $commentlen = strpos(substr($data,$headerlen),chr(0));
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false;    // Invalid header format
        }
        $comment = substr($data,$headerlen,$commentlen);
        $headerlen += $commentlen + 1;
    }
    $headercrc = "";
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false;    // invalid
        }
        $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
        $headercrc = unpack("v", substr($data,$headerlen,2));
        $headercrc = $headercrc[1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false;    // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack("V",substr($data,-8,4));
    $datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
    $isize = unpack("V",substr($data,-4));
    $isize = $isize[1];
    // decompression:
    $bodylen = $len-$headerlen-8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr($data,$headerlen,$bodylen);
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
        case 8:
            // Currently the only supported compression method:
            $data = gzinflate($body,$maxlength);
            break;
        default:
            $error = "Unknown compression method.";
            return false;
        }
    }  // zero-byte body content is allowed
    // Verifiy CRC32
    $crc   = sprintf("%u",crc32($data));
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen($data);
    if (!$lenOK || !$crcOK) {
        $error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
  }

  // The built-in function returns wrong result when input arrays have duplicate values.
  // Here is a code that works correctly: 
  private static function array_intersect($array1, $array2) {
    $result = array();
    foreach ($array1 as $val) {
      if (($key = array_search($val, $array2, TRUE))!==false) {
        $result[] = $val;
        unset($array2[$key]);
      }
    }
    return $result;
  } 
}
?>
