<?php

require_once("../log.php");
require_once("i18n-ivr.php");


Log::system("============starting outbound.vxml.php");
Log::system("http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
Log::system($_REQUEST);


$lang = $_REQUEST['lang'];
$audio_recording_url = $_REQUEST['audio_recording_url'];
$meetingUserId = $_REQUEST['meeting_user_id'];

header('Content-Type: application/voicexml+xml; charset=utf-8');
print ("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");

Log::system("audio_recording_url: $audio_recording_url");

function say($textCode) { 
  global $lang;
  echo I18NIVR::say($textCode, $lang);
}
function pause() {
  global $ivrPlatform;
  echo $ivrPlatform->pause();
}

?>

<vxml xmlns="http://www.w3.org/2001/vxml" version="2.1">
  
  <var name="meetingUserId" expr="'<?php echo $meetingUserId?>'"/>
  <var name="lang" expr="'<?php echo $lang?>'"/>

  <form>
    <block>
      <prompt bargein="false">
        <audio src="../media/guitar-jingle-1.wav"/>
        <?php pause();say("bonjour-message");pause() ?>
        <audio src="<?php echo $audio_recording_url?>"><?php say("pas-de-message")?></audio>
        <?php pause() ?>
      </prompt>
    </block>

<?php 
  if ($lang != "bam") { 
?>

    <field name="meeting_participation_status">
      <prompt>
        <?php say("assiste-evenement-1")?>
        <?php say("assiste-pas-evenement-2")?>
        <?php say("assiste-ptet-evenement-3")?>
      </prompt>
      <option dtmf="1" value="yes"/>
      <option dtmf="2" value="no"/>
      <option dtmf="3" value="maybe"/>
      <nomatch><?php say("pas-compris")?><reprompt/></nomatch>
      <noinput><?php say("pas-compris")?><reprompt/></noinput>
      <filled>
        <data src="../model/?action=change_meeting_participation&amp;meeting_user_id=<?php echo $meetingUserId?>&amp;format=xml" namelist="meeting_participation_status" name="apiCallResult"/>
        <prompt><?php say("merci")?></prompt>
        <submit next="outbound1.7.vxml.php" namelist="meetingUserId lang"/>
      </filled>
    </field>

<?php 
} else { 
?>

    <record name="audio" beep="true" maxtime="5s" finalsilence="2000ms" dtmfterm="true" type="audio/x-wav">
      <prompt timeout="10s" bargein="false">
        <?php say("si-vous-serez-present-dites-oui") ?><?php pause() ?>
        <?php say("si-pas-present-non") ?><?php pause() ?>
        <?php say("si-savez-pas-dites") ?><?php pause() ?>
      </prompt>
      <noinput><?php say("pas-compris")?></noinput>
      <catch event="connection.disconnect.hangup">
        <submit method="post" enctype="multipart/form-data" next="outbound1.5.vxml.php" namelist="meetingUserId lang audio"/>
      </catch>
      <filled>
        <submit method="post" enctype="multipart/form-data" next="outbound1.5.vxml.php" namelist="meetingUserId lang audio"/>
      </filled>
    </record>

<?php
}
?>

  </form>
</vxml>
