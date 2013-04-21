<?php
require_once("../log.php");
require_once("i18n-ivr.php");

Log::system("starting inbound.vxml.php");
Log::system($_SERVER['REQUEST_URI']);

header('Content-Type: application/voicexml+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8"?>');
?>

<vxml xmlns="http://www.w3.org/2001/vxml" version="2.1">
   <form>
     <property name="bargein" value="false"/>
     <block>
       <prompt bargein="false">
         <?php echo I18NIVR::say("bonjour-bienvenue","fr")?>
         <?php pause() ?>
         <?php echo I18NIVR::say("bonjour-bienvenue","bam")?>
       </prompt>
     </block>
     <record name="msg" beep="true" maxtime="10s" finalsilence="4000ms" dtmfterm="true" type="audio/x-wav">
       <prompt timeout="10s" bargein="false">
         <?php pause() ?>
         <?php echo I18NIVR::say("laisser-message","fr")?>
         <?php pause() ?>
         <?php echo I18NIVR::say("laisser-message","bam")?>
       </prompt>
       <noinput>
         <prompt>
           <?php echo I18NIVR::say("pas-compris","fr") . I18NIVR::say("ressayer","fr")?>
           <?php pause() ?>
           <?php echo I18NIVR::say("pas-compris","bam") . I18NIVR::say("ressayer","bam")?>
         </prompt>
       </noinput>
       <catch event="connection.disconnect.hangup">
         <submit next="inbound2.vxml.php" method="post" namelist="msg" enctype="multipart/form-data" maxage="0"/>
       </catch>
     </record>
     <filled>
       <submit next="inbound2.vxml.php" method="post" namelist="msg" enctype="multipart/form-data" maxage="0"/>
     </filled>
   </form>
</vxml>
