<?php

class XMLSerializer {

  static function element_name($key,$closing) {
    if (is_string($key))
      print("<".($closing?"/":"").$key.">");
    else
      print("<".($closing?"/":"")."item>");
  }

  public static function serialize($object,$name) {
    if ($name) print("<".$name.">");
    if (is_array($object)) {
      foreach(array_keys($object) as $key) {
        print(self::element_name($key,FALSE));
        self::serialize($object[$key],null);
        print(self::element_name($key,TRUE));
      }
    } else {
      print($object);
    }
    if ($name) print("</".$name.">");
  }
}