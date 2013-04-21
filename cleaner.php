<?php

header('Content-Type: text/plain; charset=utf-8');

//unlink("api.php");
//unlink("controller.php");
//unlink("dirutils.php");
//unlink("i18n-ivr.php");
//unlink("inbound.vxml.php");
//unlink("inbound2.vxml.php");
//unlink("makefile");
//unlink("outbound.vxml.php");
//unlink("outbound2.vxml.php");
//unlink("platform.php");
//unlink("rb.php");
//unlink("voicexmlhub.php");
//unlink("xmlserializer.php");
//print("=== . ===================================\n");
//print_r(scandir("."));
//
//unlink("model/media/audio_en.wav");
//unlink("model/media/audio_fr.wav");
//rmdir("model/media");
//unlink("model/phplog.txt");
//unlink("model/systemlog.txt");
//
//

print("=== . ===================================\n");
print_r(scandir("."));
print("=== audio ===================================\n");
print_r(scandir("audio"));
print("=== media ===================================\n");
print_r(scandir("media"));
print("=== ivr ===================================\n");
print_r(scandir("ivr"));
print("=== model ===================================\n");
print_r(scandir("model"));
