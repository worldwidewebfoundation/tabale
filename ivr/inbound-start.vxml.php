<?php
require_once("../log.php");

Log::system("starting inbound-start");
Log::system($_SERVER['REQUEST_URI']);

$sessionId = uniqid();

header('Content-Type: application/voicexml+xml; charset=utf-8');
print('<?xml version="1.0" encoding="utf-8"?>');
?>
<vxml version="2.1" xmlns="http://www.w3.org/2001/vxml">
 <var name="callerId" expr="session.connection.originator.uri"/>
 <var name="sessionId" expr="'<?php echo $sessionId ?>'"/>
  <form>
    <block>     
      <submit next="inbound-identify.vxml.php" method="get" namelist="callerId sessionId"/>
    </block>
  </form>
</vxml>