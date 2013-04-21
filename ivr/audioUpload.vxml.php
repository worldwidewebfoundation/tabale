<?php

require_once("../log.php");
include_once('i18n-ivr.php');

Log::system("==============================\nstarting audioUpload.vxml.php");
Log::system($_REQUEST);
Log::system($_FILES);

// handle upload of audio message passed
$lang = $_REQUEST['lang'];
$msg = "";
$meetingUserId = $_REQUEST['meetingUserId'];
$comeback = isset($_REQUEST['comeback']) ? $_REQUEST['comeback'] : null;
$userId = isset($_REQUEST['userId']) ? $_REQUEST['userId'] : null;
$tmpFile=$_FILES['msg']['tmp_name'];

if (is_uploaded_file($tmpFile)) {
  Log::system("audioUpload: file uploaded");
  $fileName = "message-meeting-" . $meetingUserId;

  // the recorded format could be in various formats, depending on the platform.
  // so let the platform-dependent module handle conversion
  $wavFileName = $fileName . ".wav";
  $rawFileName = $fileName . ".raw";
  $result = move_uploaded_file($tmpFile, "../media/".$rawFileName);
  if ($result==TRUE) {
    Log::system("audioUpload: file moved at ../media/$rawFileName");
    $convertOutput = $ivrPlatform->convertAudio("../media/".$rawFileName, "../media/".$wavFileName);
    Log::system("audioUpload: file ../media/$rawFileName converted to ../media/$wavFileName, result: $convertOutput");
    if ($convertOutput) {
      Log::system("error converting audio:\n".implode("\n",$convertOutput));
    } else {
      Log::system("audioUpload: audio file converted at ../media/$wavFileName");
    }
  } else {
    Log::system("error moving uploaded file. ". $_FILES['msg']['error']);
  }
} else {
  Log::system("Error: audio file wasn't uploaded.");
}

Log::system("audioUpload: finished");
function pause() {
  global $ivrPlatform;
  echo $ivrPlatform->pause();
}

header('Content-Type: application/voicexml+xml; charset=utf-8');
?>
<vxml xmlns="http://www.w3.org/2001/vxml" version="2.1">
  <var name="meetingUserId" expr="'<?php echo $meetingUserId?>'"/>
  <form>
    <block>
      <var name="action" expr="'record_message_from_outgoing_call'"/>
      <var name="filename" expr="'<?php echo "media/".$wavFileName?>'"/>
      <var name="format" expr="'xml'"/>
      <data src="../model/" method="post" namelist="action filename format meetingUserId"/>

<?php if ($comeback) { ?>
      <prompt><?php echo I18NIVR::say("merci", $lang); pause() ?></prompt>
      <goto next='<?php echo "inbound-events.vxml.php?userId=$userId&amp;lang=$lang#$comeback"?>'/>
<?php } else { ?>
      <prompt><?php echo I18NIVR::say("au-revoir", $lang); pause() ?></prompt>
      <exit/>
<?php } ?>
    </block>
  </form>
</vxml>

