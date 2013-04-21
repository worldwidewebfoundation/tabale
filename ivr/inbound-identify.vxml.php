<?php
require_once('../log.php');
require_once('model.php');

Log::system("starting inbound-identify");
Log::system($_SERVER['REQUEST_URI']);

// expected HTTP parameters
if (!isset($_REQUEST['callerId'])) { Log::system("error: callerId not set"); exit; }
$callerId = $_REQUEST['callerId'];
$callerId = "4444444";
// We need to know who is the user and his radio
$user = Model::findCaller($callerId);

if ($user) {
  // this is a known caller - we won't need to create a user later
  $userId = $user['id'];
  Log::system("user languageId: "); Log::system($user['languages']);
  $lang = Model::languageCode($user['languages'][0]);
  Log::system("user lang: $lang");
  if (!$lang) { 
    Log::system("Error, user with invalid language: ");
    Log::system($user);
  }
  Log::system("this is a known caller: "); Log::system($user);
} else {
  // this is either an anonymous or a new caller: 
  // in either case, we'll create a new user
  $userId = 0;
  $lang="_all_";
  Log::system("this is either a new caller, on an anonymous call");
}


header('Content-Type: application/voicexml+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8"?>');
?>

<vxml xmlns="http://www.w3.org/2001/vxml" version="2.0">

  <property name="inputmodes" value="dtmf"/>

  <form>
    <block>
      <var name="userId" expr="<?php echo $userId?>"/>
      <var name="lang" expr="'<?php echo $lang?>'"/>
  <?php if ($userId == 0) { ?>
      <submit maxage="0" next="inbound-message.vxml.php" namelist="userId lang"/>
  <?php } else { ?>
      <submit maxage="0" next="inbound-menu.vxml.php" namelist="userId lang"/>
  <?php } ?>
    </block>
  </form>
</vxml>
