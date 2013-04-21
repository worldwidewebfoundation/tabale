<?php

require_once("../log.php");
require_once("i18n-ivr.php");


Log::system("starting outbound1.7.vxml.php");
Log::system("http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);


$lang = $_REQUEST['lang'];
$meetingUserId = $_REQUEST['meetingUserId'];

function say($textCode) { 
  global $lang;
  echo I18NIVR::say($textCode, $lang);
}

header('Content-Type: application/voicexml+xml; charset=utf-8');
print ("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
?>

<vxml xmlns="http://www.w3.org/2001/vxml" version="2.1">

  <var name="meetingUserId" expr="'<?php echo $meetingUserId?>'"/>
  <var name="lang" expr="'<?php echo $lang?>'"/>

  <form>
    <record name="msg" beep="true" maxtime="60s" finalsilence="4000ms" dtmfterm="true" type="audio/x-wav">
      <prompt timeout="10s" bargein="false">
        <?php say("laisser-message")?>
      </prompt>
      <noinput><?php say("pas-compris")?></noinput>
      <catch event="connection.disconnect.hangup">
        <submit next="audioUpload.vxml.php" method="post" namelist="msg meetingUserId lang" enctype="multipart/form-data" maxage="0"/>
      </catch>
      <filled>
        <submit next="audioUpload.vxml.php" method="post" namelist="msg meetingUserId lang" enctype="multipart/form-data" maxage="0"/>
      </filled>
    </record>

    <block>
      <prompt><?php say("merci")?></prompt>
      <prompt><?php say("au-revoir")?></prompt>
      <prompt><audio src="../media/guitar-jingle-2.wav'?>"/></prompt>
    </block>
  </form>
</vxml>