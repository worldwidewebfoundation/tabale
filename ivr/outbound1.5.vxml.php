<?php

require_once("../log.php");
require_once('i18n-ivr.php');

Log::system("starting outbound1.5.vxml.php");
Log::system($_REQUEST);
Log::system($_FILES);

// handle upload of yes/no/maybe audio message passed
$lang = $_REQUEST['lang'];
$meetingUserId = $_REQUEST['meetingUserId'];
$tmpFile=$_FILES['audio']['tmp_name'];

if (is_uploaded_file($tmpFile)) {

  Log::system("outbound1.5: file uploaded");
  $fileName = "../media/answer-meeting-" . $meetingUserId;

  // the recorded format could be in various formats, depending on the platform.
  // so let the platform-dependent module handle conversion
  $wavFileName = $fileName . ".wav";
  $rawFileName = $fileName . ".raw";
  $result = move_uploaded_file($tmpFile, $rawFileName);
  if ($result==TRUE) {
    Log::system("outbound1.5: file moved at $rawFileName");
    $convertOutput = $ivrPlatform->convertAudioForJulius($rawFileName, $wavFileName);
    if ($convertOutput) {
      Log::system("error converting audio:\n".implode("\n",$convertOutput));
    } else {
      Log::system("outbound1.5: file converted at $wavFileName");
    }
  } else {
    Log::system("error moving uploaded file. ". $_FILES['audio']['error']);
  }
} else {
  Log::system("Error: audio file wasn't uploaded.");
}

// Now transmit the file to the ASR
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, ASRAPI);
curl_setopt($ch, CURLOPT_POSTFIELDS, array('audio'=>"@".$wavFileName));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
$asrResult = curl_exec($ch);
    
if ($asrResult == FALSE) {

} else {
   Log::system("ASR post result: ");
   Log::system($asrResult);
}
    
$info = curl_getinfo($ch);
curl_close($ch);
Log::system("done");

switch($asrResult) {
case 1:  $asrResult="yes"; break;
case 2:  $asrResult="no"; break;
case 3:  $asrResult="maybe"; break;
default: $asrResult="unknown"; break;
}

// and delete the file
unlink($wavFileName);


Log::system("outbound1.5: finished");

header('Content-Type: application/voicexml+xml; charset=utf-8');
?>
<vxml version="2.1">
  <var name="meetingUserId" expr="'<?php echo $meetingUserId?>'"/>
  <var name="lang" expr="'<?php echo $lang?>'"/>
  <form>
    <block>
      <var name="meeting_participation_status" expr="'<?php echo $asrResult; ?>'"/>
      <data src="../model/?action=change_meeting_participation&amp;meeting_user_id=<?php echo $meetingUserId?>&amp;format=xml" namelist="meeting_participation_status" name="asrResult"/>
      <submit next="outbound1.7.vxml.php" namelist="meetingUserId lang"/>
    </block>
  </form>
</vxml>

