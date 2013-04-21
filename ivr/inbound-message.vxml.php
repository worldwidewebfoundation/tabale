<?php
require_once("../log.php");
require_once("i18n-ivr.php");

Log::system("starting inbound-message.vxml.php");
Log::system($_SERVER['REQUEST_URI']);

// expected HTTP parameters
if (!isset($_REQUEST['userId'])) { Log::system("error: userId not set"); exit; }
$userId = $_REQUEST['userId'];
if (!isset($_REQUEST['lang'])) { Log::system("error: lang not set"); exit; }
$lang = $_REQUEST['lang'];

function say($message, $messageLang) {
  global $lang;

  if ($lang==="_all_" or $lang===$messageLang) {
    echo I18NIVR::say($message, $messageLang);
  }
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

   <form>
     <property name="bargein" value="false"/>
     <block>
       <prompt bargein="false">
  <?php say("bonjour-bienvenue","fr") ?>
         <?php pause() ?>
  <?php say("bonjour-bienvenue","bam")?>
       </prompt>
     </block>
     <record name="msg" beep="true" maxtime="10s" finalsilence="4000ms" dtmfterm="true" type="audio/x-wav">
       <prompt timeout="10s" bargein="false">
         <?php pause() ?>
         <?php say("laisser-message","fr") ?>
         <?php pause() ?>
         <?php say("laisser-message","bam") ?>
       </prompt>
       <noinput>
         <prompt>
  <?php say("pas-compris","fr"); say("ressayer","fr") ?>
           <?php pause() ?>
  <?php say("pas-compris","bam"); say("ressayer","bam")?>
         </prompt>
       </noinput>
       <catch event="connection.disconnect.hangup">
         <submit next="inbound-message-upload.vxml.php" method="post" namelist="msg lang" enctype="multipart/form-data" maxage="0"/>
       </catch>
     </record>
     <filled>
       <submit next="inbound2.vxml.php" method="post" namelist="msg" enctype="multipart/form-data" maxage="0"/>
     </filled>
   </form>
</vxml>
