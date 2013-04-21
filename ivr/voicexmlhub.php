<?php
include_once("log.php");
if (isset($_REQUEST['mode']) && $_REQUEST['mode']==='outbound') {
  Log::system("starting outbound call");
  include("outbound.vxml.php");
} else {
  Log::system("receiving inbound call");
  include("inbound.vxml.php");
}
?>
