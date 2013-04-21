<?php
require_once("../log.php");
require_once("model.php");
require_once("xmlserializer.php");

Log::system("API call");
Log::system($_SERVER['REQUEST_URI']);


if (Controller::init() == FALSE) {
  header('HTTP/1.0 500 Internal Server Error'); // TODO: use more detailed response codes
  print("Failed to talk to database");
  die();
}

if (isset($_POST['action']) or isset($_GET['action'])) {
  $action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];

  switch ($action) {
  case "add_meeting": $result = Controller::add_meeting($_POST); break;
  case "upload_wav": $result = Controller::upload_wav($_GET); break;
  case "add_user": $result = Controller::add_user($_POST); break;
  case "delete_meeting": $result = Controller::delete_meeting($_POST); break;
  case "delete_user": $result = Controller::delete_user($_POST); break;
  case "change_meeting_participation": $result = Controller::change_meeting_participation($_GET); break;
  case "request_participation": $result = Controller::request_participation($_POST); break;
  case "get_meetings": $result = Controller::get_meetings(); break;
  case "get_users": $result = Controller::get_users(); break;
  case "get_user": $result = Controller::get_user($_GET); break;
  case "get_languages": $result = Controller::get_languages(); break;
  case "change_user": $result = Controller::change_user($_POST); break;
  case "change_meeting": $result = Controller::change_meeting($_POST); break;
  case "delete_meeting_user": $result = Controller::delete_meeting_user($_POST); break;
  case "add_meeting_user": $result = Controller::add_meeting_user($_POST); break;
  case "delete_language_meeting": $result = Controller::delete_language_meeting($_POST); break;
  case "set_meeting_languages": $result = Controller::set_meeting_languages($_POST); break;
  case "add_language_meeting": $result = Controller::add_language_meeting($_POST); break;
  case "get_audio": $result = Controller::get_audio($_GET); break;
  case "clear_user_log": $result = Controller::clear_user_log(); break;
  case "clear_system_log": $result = Controller::clear_system_log(); break;
  case "ping_user_answered": $result = Controller::ping_user_answered($_POST); break;
  case "register_incoming_call": $result = Controller::register_incoming_call($_POST); break;
  case "record_message_from_outgoing_call": $result = Controller::record_message_from_outgoing_call($_POST); break;
  default: $result = array("status"=>"FAIL", "message"=>"Unknown request: $action");
  }
} else {
  $result = array("status"=>"FAIL", "message"=>"missing action parameter");
}

//Log::system("### Result");
//Log::system($result);
//Log::system("############### End");


if ($result['status'] == "FAIL" || $result['status'] == "fail") {
  Log::system("**** API ERROR: sending 400. Result: ");
  Log::system($result);
  header('HTTP/1.0 400 Bad Request'); // TODO: use more detailed response codes
  print $result['message'];

} else {

  if ((isset($_POST['format']) && $_POST['format']=="xml") || (isset($_GET['format']) && $_GET['format']=="xml")) {
    header('Content-Type: application/xml');
    XMLSerializer::serialize($result,"result");
  } else { // by default, return json
    header('Content-Type: application/json');
    print(json_encode($result));
  }
}
?>

