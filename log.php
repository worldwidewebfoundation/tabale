<?php
// logging functions
include_once("passwords.php");

date_default_timezone_set('UTC');

class Log {
  private static function syslogFileName() {
    return APPLICATIONROOTDIR . "/systemlog.txt"; 
  }
  private static function userlogFileName() { 
    return APPLICATIONROOTDIR . "/userlog.txt"; 
  }

  private static $logfile;

  private static function write($object) {
    $success = fwrite(self::$logfile,date("j/n/y H:i").' - '.(is_string($object)?$object:print_r($object,TRUE))."\n");
    if (!$success) {
      error_log("Log::write: failed to write log entry");
    }
    fclose(self::$logfile);
  }

  public static function system($object) {
    if (file_exists(self::syslogFileName()) == FALSE) {
      if (touch(self::syslogFileName()) == FALSE) {
        error_log("Log::system: couldn't create system log file");
        exit;
      }
    }
    self::$logfile = fopen(self::syslogFileName(),"a+");
    if (!self::$logfile) {
      error_log("Log::system: couldn't write to file " . self::syslogFileName());
    }
    self::write($object);
  }

  public static function user($object) {
    if (file_exists(self::userlogFileName()) == FALSE) {
      if (touch(self::userlogFileName()) == FALSE) {
        error_log("Log::user: couldn't create user log file");
        exit;
      }
    }
    self::$logfile = fopen(self::userlogFileName(),"a+");
    self::write($object);
  }
  
  public static function clearSystemLog() {
    return (unlink(self::syslogFileName()) && touch(self::syslogFileName()));
  }

  public static function clearUserLog() {
    return (unlink(self::userlogFileName()) && touch(self::userlogFileName()));
  }
}
?>
