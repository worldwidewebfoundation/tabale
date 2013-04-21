<?php

require_once("../log.php");
include_once('i18n-ivr.php');

Log::system("starting inbound2.vxml.php");
Log::system($_SERVER['REQUEST_URI']);

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

header('Content-Type: application/voicexml+xml; charset=utf-8');
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
        <?php echo I18NIVR::say("merci","fr") ?>
        <?php echo I18NIVR::say("au-revoir","fr") ?>
        <?php pause() ?>
        <?php echo I18NIVR::say("merci","bam") ?>
        <?php echo I18NIVR::say("au-revoir","bam") ?>
      </prompt>

      <exit/>
    </block>
  </form>
</vxml>
