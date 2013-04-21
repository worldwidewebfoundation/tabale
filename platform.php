<?php
// Wrapper class containing functions with platform-dependent code
// functions available:
//
// Platform::convertAudio($inFileName, $outFileName)
// convert audio file inFileName into WAV format, as file $outFileName
// This must be used on audio files retrieved from an <audio> tag
//
// Platform::call($params)
// make an outbound call
// parameters: 
// - $params['phone'] - phone number to call
// - other keys found in $param will be passed to the VoiceXML that's run when
//   the call is picked up. The URL of that VoiceXML file should be defined in

class IvrPlatform {

  private $params;

  function IvrPlatform($params) {
    $this->params=$params;
  }

  public function sox() { 
    // this is necessary because upload_wav in model.php needs it. It should do its file conversion with the ivr platform, but that would mean 
    // doing a double upload: application -> platform -> model
    return $this->params['sox'];
  }

  public function convertAudioForJulius($inFileName, $outFileName) {
    $sox = $this->params['sox'];
    exec("$sox -e u-law -r 8k $inFileName -r 8k -b 16 -e signed-integer -c 1 $outFileName", $output, $retval);
    if ($retval != 0) {
      error_log("julius sox error. output: "); error_log($output);
      return $output;
    } 
    unlink($inFileName);
  }

  // convert an audio file (typically received from a <record> tag) from 
  // 8k raw ulaw to 8k 8-bit u-law WAV. Used because emerginov outputs the former
  // for evolution or prophecy, this does nothing.
  // returns the output from the conversion if there was an error, FALSE otherwise
  public function convertAudio($inFileName, $outFileName) {
    switch ($this->params['platformType']) {
    case "emerginov":
      $sox = $this->params['sox'];
      try {
        exec("$sox -e u-law -r 8k $inFileName -r 8k -b 8 -e a-law -c 1 $outFileName", $output,$retval);
      } catch (Exception $e) {
        error_log("Failure in call to sox:");
        error_log(e);
        return false;
      }
          
      if ($retval != 0) {
        error_log("sox error. output: "); error_log($output);
        return $output;
      }
      unlink($inFileName);
      break;
    default:
    // just rename file as it is already in wav format with those platforms
      return !rename($inFileName, $outFileName);
    }
  }

  // make a call using the platform's outbound capabilities
  // parameters: 
  // - $params['phone'] - phone number to call
  // - other params will be passed to the VoiceXML

  // Note: there should be a URL parameter for the voicexml file to
  // execute once the call is made, but for evolution and prophecy,
  // the number is fixed and set in the application parameters
  // (so it would be misleading if this API required the URL and
  // didn't use it).


  public function call($params) {

    error_log("about to call");
    error_log(print_r($params, TRUE));

    $phoneNumberToCall = $params['phone'];
    if ($this->params['platformType']==="evolution" || $this->params['platformType']==="prophecy") {
      // See http://docs.voxeo.com/ccxml/1.0-final/frame.jsp?page=t_7ccxml10.htm
      // and http://www.vxml.org/frame.jsp?page=tokencalls.htm for VoiceXML dialout
      // example trigger URL: http://192.168.1.99:9998/SessionControl/VoiceXML.start

      if ($this->params['platformType']==="evolution") {
        $url = "http://api.voxeo.net/SessionControl/4.5.41/VoiceXML.start?tokenid=" . $this->params['evolutionToken'] . "&";
      } else
        $url = "http://127.0.0.1:9998/SessionControl/VoiceXML.start?tokenid=" . $this->params['evolutionToken'] . "&";

      $url .= "numbertodial=".urlencode(utf8_decode($phoneNumberToCall)) .
        "&" . self::uriParamStringFromCalloutParams($params);

      // with Prophecy or evolution, the http return code is always 200, but the 
      // result (busy, fail, success) is in the body
      $ch = curl_init($url);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
      $success = curl_exec($ch);
      $info = curl_getinfo($ch);
      curl_close($ch);
      return $success && $info['http_code'] == 200;

    } else if ($this->params['platformType']==="emerginov") {

      // see https://developers.emerginov.org/wiki/index.php/Emerginov_User_Guide_Cheat_Sheet#Emerginov_PHP_built-in_functions

      if ($this->params['platformType']==="emerginov") {
        include_once("Emerginov.php");
      }

      $emerginov = new Emerginov($this->params['emerginovApiLogin'], $this->params['emerginovApiPlatform']);

      // remove sip: for sip addresses
      if (strpos($phoneNumberToCall,"sip:") === 0) {
        $phoneNumberToCall = substr($phoneNumberToCall, 4);
      }
     
      $outboundVoiceXMLURL = "/ivr/outbound.vxml.php?" . self::uriParamStringFromCalloutParams($params);


      $result = $emerginov->Call($phoneNumberToCall, $outboundVoiceXMLURL);

      return true; // don't trust $result->Success, as it will be false if user doesn't pick up
    } else {
      error_log("unknown ivrPlatform: ".$this->params['platformType']);
    }
  }


  // select the correct TTS voice from the language requested
  // $lang parameter is a 3-letter language code (ISO 639)
  // returns the string to put in xml:lang in the voicexml file
  public function ttsVoice($lang) {
    switch ($this->params['platformType']) {
    case "emerginov":
      switch($lang) {
      case "en": return "en-GB-Elizabeth";
      // also valid are:
      //     [en-GB-Elizabeth] => Elizabeth
      //     [en-GB-Bibi] => Bibi
      //     [en-GB-Freddy] => Freddy
      //     [en-EN] => Elizabeth
      //     [EN] => Elizabeth
      //     [en] => Elizabeth
      //     [en-GB] => Bibi
      //     [GB] => Bibi
      case "fr": return "fr-FR-Loic";
    // or      
  //    [fr-FR-Loic] => Loic
  //    [fr-FR-Agnes] => Agnes
  //    [fr-FR-Philippe] => Philippe
  //    [fr-FR-Lise] => Lise
  //    [fr-FR-Lison] => Lison
  //    [fr-FR-Julie] => Julie
  //    [fr-FR-JulieXP] => JulieXP
  //    [fr-FR-AgnesXP] => AgnesXP
  //    [fr-FR-Melodine] => Melodine
  //    [fr-FR-Zozo] => Zozo
  //    [fr-FR-Guy_dialogue] => Guy_dialogue
  //    [fr-FR-Guy_dictee] => Guy_dictee
  //    [fr-FR-Guy_fort] => Guy_fort
  //    [fr-FR-Guy] => Guy
  //    [fr-FR-Sidoo] => Sidoo
  //    [fr-FR-Ramboo] => Ramboo
  //    [fr-FR-DarkVadoor] => DarkVadoor
  //    [fr-FR-Chut] => Chut
  //    [fr-FR-Guy_voix_basse] => Guy_voix_basse
  //    [fr-FR-Bicool] => Bicool
  //    [fr-FR-ChatPotte] => ChatPotte
  //    [fr-FR-Electra] => Electra
  //    [fr-FR-JeanJean] => JeanJean
  //    [fr-FR-Papi] => Papi
  //    [fr-FR-Yeti] => Yeti
  //    [fr-FR-Sorciere] => Sorciere
  //    [fr-FR-Julie3000] => Julie3000
  //    [fr-FR] => Agnes
  //    [fr] => Agnes
  //    [FR] => Agnes

      case "spa": return "es-ES";
//    [es-ES] => Marta
//    [ES] => Marta
//    [es] => Marta
//    [es-ES-Pedro] => Pedro
//    [es-ES-Marta] => Marta

      case "wol": return "wol-SN-Fati";
      default: return "fr-FR";
      }
      break;
    case "evolution":
      switch ($lang) {
      case "en": return "en-gb";
      case "fr": return "fr-fr";
      case "wol": return "fr-fr";
      case "spa": return "es-es";
      default: return "fr-fr";
      }
// American English 	en-us 	en-us 	Female
// American Spanish 	es-mx 	es-mx 	Female
// Argentine Spanish 	es-ar 	es-ar 	Male
// Australian English 	en-au 	en-au 	Female
// Brazilian Portuguese 	pt-br 	pt-br 	Female
// British English 	en-gb 	en-gb 	Female
// Canadian French 	fr-ca 	fr-ca 	Female
// Castilian Spanish 	es-es 	es-es 	Female
// Catalan 	ca-es 	ca-es 	Female
// Chilean Spanish 	es-cl 	es-cl 	Female
// Columbian Spanish 	es-co 	es-co 	Female
// Danish 		da-dk 	Female
// Dutch 	nl-nl 	nl-nl 	Female
// European Portuguese 	pt-pt 	pt-pt 	Female
// Finnish 	fi-fi 	fi-fi 	Female
// French 	fr-fr 	fr-fr 	Female
// Galician 	gl-es 	gl-es 	Female
// German 	de-de 	de-de 	Female
// Greek 	el-gr 	el-gr 	Female
// Italian 	it-it 	it-it 	Female
// Mandarin Chinese 		zh-cn 	Female
// Norwegian 	no-no 	no-no 	Female
// Polish 	pl-pl 	pl-pl 	Female
// Russian 		ru-ru 	Female
// Swedish 	sv-se 	sv-se 	Female
// Turkish 		tr-tr 	Female
// Valencian 	x-va 	x-va 	Female

      break;
    case "prophecy":
      // only english by default
      return "en";
    break;
    default: return "en";
    }
  }


 // the time attribute in <break> is ignored in VoiceGlue. We must use a function instead
  public function pause($durationInMs = 2000) {
    switch ($this->params['platformType']) {
    case "emerginov": echo "\break{".$durationInMs."}"; break;
    case "evolution":
    case "prophecy": echo "<break time='".$durationInMs."ms'/>"; break;
    }
  }

  // ################ PRIVATE #################################################

  // put application-specific parameters into an URL param string
  // for outbound calls
  private static function uriParamStringFromCalloutParams($params) {
    $s = "mode=outbound";
    foreach($params as $key => $val) {
      if ($key != 'phone')
        $s .= "&" . urlencode(utf8_decode($key)) . "=" . urlencode(utf8_decode($val));
    }
    return $s;
  }
}
?>
