<?php
require_once("../log.php");
require_once("../platform.php");
require_once("i18n-ivr.php");
require_once("model.php");

Log::system("starting inbound-events.vxml.php");
Log::system($_SERVER['REQUEST_URI']);

// expected HTTP parameters
if (!isset($_REQUEST['userId'])) { Log::system("error: userId not set"); exit; }
$userId = intval($_REQUEST['userId']);
if (!isset($_REQUEST['lang'])) { Log::system("error: lang not set"); exit; }
$lang = $_REQUEST['lang'];


$userUnconfirmedMeetings = Model::userUnconfirmedMeetings($userId);

function say($textCode) { 
  global $lang;
  echo I18NIVR::say($textCode, $lang);
}
function pause() {
  global $ivrPlatform;
  echo $ivrPlatform->pause();
}

header('Content-Type: application/voicexml+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8"?>');
?>

<vxml xmlns="http://www.w3.org/2001/vxml" version="2.1">
  <var name="userId" expr="<?php echo $userId?>"/>
  <var name="lang" expr="'<?php echo $lang?>'"/>


<?php 
    if (count($userUnconfirmedMeetings) > 0) {
      $userLangId = intval(Model::languageId($lang));
?>
  <form>
    <block>
      <prompt><?php pause(); say("your-events"); pause()?></prompt>
      <goto next="#form<?php echo $userUnconfirmedMeetings[0]['participantId']?>"/>
    </block>
  </form>

<?php
      for ($formIndex=0; $formIndex<count($userUnconfirmedMeetings); $formIndex++) {
        $userParticipation = $userUnconfirmedMeetings[$formIndex];
        $userParticipationId = $userParticipation['participantId'];
        $nextParticipationId = $formIndex === count($userUnconfirmedMeetings)-1 ? "end" : $userUnconfirmedMeetings[$formIndex + 1]['participantId'];

        // for each meeting, play the meeting's message
          // then ask for user's response, etc. Just like outbound calls

        $participationId = $userParticipation['participantId'];
        $meeting = $userParticipation['meeting'];
        $meetingLanguage = Model::findById($meeting['languages'], $userLangId);
?>
  <form id="form<?php echo $userParticipationId?>">
    <block>
      <prompt><?php say($formIndex===0 ? 'first-event' : 'next-event'); pause()?></prompt>
    </block>

    <field name="meeting_participation_status">
      <prompt bargein="true">
        <audio src="<?php echo $meetingLanguage['audioUrl']?>"/>
        <?php pause() ?>
        <?php say("assiste-evenement-1")?>
        <?php say("assiste-pas-evenement-2")?>
        <?php say("assiste-ptet-evenement-3")?>
      </prompt>
      <option dtmf="1" value="yes"/>
      <option dtmf="2" value="no"/>
      <option dtmf="3" value="maybe"/>
      <nomatch><reprompt/></nomatch>
      <noinput><?php say("pas-compris")?><reprompt/></noinput>
      <filled>
        <data src="../model/?action=change_meeting_participation&amp;meeting_user_id=<?php echo $participationId?>&amp;format=xml" namelist="meeting_participation_status" name="apiCallResult"/>
        <prompt><?php say("merci")?></prompt>
      </filled>
    </field>

    <!-- now record a message -->
    <record name="msg" beep="true" maxtime="10s" finalsilence="4000ms" dtmfterm="true" type="audio/x-wav">
      <prompt timeout="10s" bargein="false"><?php say("laisser-message"); say("termine-appuyer-touche")?></prompt>
      <noinput><?php say("pas-compris") ?></noinput>
      <catch event="connection.disconnect.hangup">
        <var name="comeback" expr="'<?php echo 'form'.$nextParticipationId?>'"/>
        <var name="meetingUserId" expr="<?php echo $participationId?>"/>
        <submit next="audioUpload.vxml.php" method="post" namelist="msg comeback meetingUserId lang userId" enctype="multipart/form-data" maxage="0"/>
      </catch>
      <filled>
        <var name="comeback" expr="'<?php echo 'form'.$nextParticipationId?>'"/>
        <var name="meetingUserId" expr="<?php echo $participationId?>"/>
        <submit next="audioUpload.vxml.php" method="post" namelist="msg comeback meetingUserId lang userId" enctype="multipart/form-data" maxage="0"/>
      </filled>
    </record>
    <filled>
      <goto next="<?php echo '#form'.$nextParticipationId?>"/>
    </filled>
  </form>

    <?php } ?>

  <form id="formend">
    <block>
      <prompt><?php say("end-events"); pause() ?></prompt>
      <submit next="inbound-menu.vxml.php#menu" namelist="userId lang"/>
    </block>
  </form>

  <?php } else { ?>
    <form id="formend">
      <block>
        <prompt>
          <?php say("no-events"); pause() ?>
        </prompt>
        <submit next="inbound-menu.vxml.php#menu" namelist="userId lang"/>
      </block>
    </form>


  <?php } ?>                         


</vxml>
