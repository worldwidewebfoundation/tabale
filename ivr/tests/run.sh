#!/bin/bash
APPLICATION_BASEURI=http://localhost/emerginov/VOICES_VMEETUP/trunk/web/ivr
#APPLICATION_BASEURI=https://public.webfoundation.org/AJVoices/ivr
MODEL_BASEURI=https://public.webfoundation.org/ajv_plt/rest/api.php

##### FUNCTIONS ##################################################
function fetch_and_validate {
  # parameters:
  # $1: url to test (without prefix)
 echo ==================================================
 echo Testing: $1
 fetch $1
 validate 
}

function fetch_and_validate_error {
  # parameters:
  # $1: url to test (without prefix)
 echo Testing: $1
 fetch $1
 validate_error 
}

function fetch {
  current_url=$1

  rm -f out.vxml
  echo CURL: $APPLICATION_BASEURI/$1
  curl -s $APPLICATION_BASEURI/$1 > out.vxml
  
  return_code=$?
  
  if [[ $return_code != 0 ]] ; then
      /bin/echo http error: $return_code
      die $return_code
  fi
}

function validate {
  java -jar jing.jar vxml21.rng out.vxml 
  
  return_code=$?
  
  if [[ $return_code != 0 ]] ; then
      /bin/echo Validity error: $return_code
      cat out.vxml
      die
  else
      /bin/echo OK 
  fi
  vxml_response=`cat out.vxml`
}


function cleanup {

echo Cleaning up.


echo all clean.

}


function die {
# $1 is the return code
  echo
  /bin/echo "ERROR fetching and validating "${APPLICATION_BASEURI}/$current_url
#  cat out.vxml
  cleanup
  exit $1
}


##### RUN THE TESTS #########################
/bin/echo "Running tests"


### syntax check all PHP files
for file in ../*.php
do
  php -l $file
  if [[ $? != 0 ]]; then exit; fi
done

### validate VXML results from the api
callerid="TEST_${RANDOM}`date +%s`"
date=`date`
startTime=`date +%s`

fetch_and_validate inbound.vxml.php
fetch_and_validate inbound-start.vxml.php
fetch_and_validate "inbound-identify.vxml.php?callerId=123456"
fetch_and_validate "inbound-identify.vxml.php?callerId=maxfroumentin"
fetch_and_validate "inbound-menu.vxml.php?lang=fr&userId=2"
fetch_and_validate "inbound-message.vxml.php?lang=fr&userId=2"
fetch_and_validate "inbound-message.vxml.php?lang=fr&userId=_all_"
fetch_and_validate "inbound-events.vxml.php?lang=fr&userId=2"

# record a message as an new (possibly anonymous) user
echo "Testing: audioUpload.vxml.php"
curl -s -F lang=fr -F msg=@useraudiotest.wav -F comeback=form2 -F meetingUserId=9 $APPLICATION_BASEURI/audioUpload.vxml.php > out.vxml
validate

cleanup

/bin/echo " Success!"





