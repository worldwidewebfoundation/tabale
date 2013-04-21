<?php
require_once("../log.php");
require_once("../platform.php");
require_once("i18n-ivr.php");

Log::system("starting inbound-menu.vxml.php");
Log::system($_SERVER['REQUEST_URI']);

// expected HTTP parameters
if (!isset($_REQUEST['userId'])) { Log::system("error: userId not set"); exit; }
$userId = intval($_REQUEST['userId']);

// expected HTTP parameters
if (!isset($_REQUEST['lang'])) { Log::system("error: lang not set"); exit; }
$lang = $_REQUEST['lang'];


header('Content-Type: application/voicexml+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8"?>');
?>

<vxml xmlns="http://www.w3.org/2001/vxml" version="2.1">
  <var name="userId" expr="<?php echo $userId?>"/>
  <var name="lang" expr="'<?php echo $lang?>'"/>
  <property name="bargein" value="true"/>
  <property name="inputmodes" value="dtmf"/>

  <form>
    <block>
      <prompt>
        <?php echo I18NIVR::say("bonjour-bienvenue",$lang); $ivrPlatform->pause(); ?>
      </prompt>
      <goto next="#menu"/>
    </block>  
  </form>

  <menu id="menu">
    <prompt>
      <?php echo I18NIVR::say("message-or-events",$lang) ?>
    </prompt>
    <choice dtmf="1" next="#message"/>
    <choice dtmf="2" next="#events"/>
    <noinput><?php echo I18NIVR::say("pas-compris",$lang) . I18NIVR::say("ressayer",$lang) ?></noinput>
    <nomatch><reprompt/></nomatch>
  </menu>

  <form id="message">
    <block>
      <submit next="inbound-message.vxml.php" namelist="lang userId"/>
    </block>
  </form>

  <form id="events">
    <block>
      <submit next="inbound-events.vxml.php" namelist="lang userId"/>
    </block>
  </form>

</vxml>
