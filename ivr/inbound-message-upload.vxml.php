<?php

require_once("../log.php");
include_once('i18n-ivr.php');

Log::system("starting inbound-message-upload.vxml.php");
Log::system($_SERVER['REQUEST_URI']);

// expected HTTP parameters
if (!isset($_REQUEST['userId'])) { Log::system("error: userId not set"); exit; }
$userId = intval($_REQUEST['userId']);
if (!isset($_REQUEST['lang'])) { Log::system("error: lang not set"); exit; }
$lang = $_REQUEST['lang'];

// handle upload of audio message passed
$msg = "";
$tmpFile=$_FILES['msg']['tmp_name'];
if (is_uploaded_file($tmpFile)) {

  // the recorded format could be in various formats, depending on the platform.
  // so let the platform-dependent module handle conversion
  $fileName = "message-" . md5_file($tmpFile);
  $wavFileName = $fileName . ".wav";
  $rawFileName = $fileName . ".raw";

  $result = move_uploaded_file($tmpFile, "../media/".$rawFileName);
  if ($result==TRUE) {
    $convertOutput = $ivrPlatform->convertAudio("../media/".$rawFileName, "../media/".$wavFileName);
    if ($convertOutput) {
      Log::system("error converting audio:\n".implode("\n",$convertOutput));
    }
  } else {
    Log::system("error moving uploaded file. ". $_FILES['msg']['error']);
  }
} else {
  Log::system("Error: audio file wasn't uploaded.");
}

Log::system("message uploaded");

function say($message, $messageLang) {
  global $lang;
  if ($lang==="_all_" or $lang===$messageLang) {
    echo I18N::say($message, $messageLang);
  }
}
function pause() {
  global $ivrPlatform;
  echo $ivrPlatform->pause();
}

header('Content-Type: application/voicexml+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8"?>');
?>
<vxml version = "2.1">
  <form>
    <block>
      <var name="action" expr="'register_incoming_call'"/>
      <var name="filename" expr="'<?php echo 'media/'.$wavFileName?>'"/>
      <var name="callerid" expr="session.connection.remote.uri"/>
      <var name="localuri" expr="session.connection.local.uri"/>
      <var name="protocol" expr="session.connection.protocol.name"/>
      <var name="protocolUUI" expr="session.connection.protocol[session.connection.protocol.name].uui || 'unknown'"/>
      <var name="protocolVersion" expr="session.connection.protocol.version || 'unknown'"/>
      <var name="format" expr="'xml'"/>
      <data src="../model/" method="post" namelist="action filename callerid format protocol protocolUUI localuri protocolVersion"/>
      <prompt bargein="false">
        <?php say("merci","fr") ?>
        <?php say("au-revoir","fr") ?>
        <?php pause() ?>
        <?php say("merci","bam") ?>
        <?php say("au-revoir","bam") ?>
      </prompt>

      <exit/>
    </block>
  </form>
</vxml>
