tabale
======

An event organisation web application, with multiple language support and IVR functionality

This application was written as part of EU project [VOICES](http://mvoices.eu/). It is currently deployed on Orange's Emerginov platform, and used by [Sahel Eco](http://www.saheleco.net/) to organise community events in various cities of Mali.

More information about the project can be found on the VOICES page, the [Web Foundation](http://webfoundation.org) or by contacting the author (max.froumentin@gmail.com)

Installation
============

If you wish to install this application, you need a LAMP server and access to a telephony platform that supports VoiceXML. Currently, the only publicly available platform supported is Voxeo: either Evolution (hosted) or Prophecy (downloadable). However it is easy to port it to other platforms that support both inbound and outbound calls.

* install all the files on the server
* specify the parameters (MySQL credentials, etc) in passwords.php
* setup the IVR platform and specify http://yourserver.com/../ivr/inbound.vxml.php as the inbound endpoint VoiceXML

Legalese
========

Copyright @ World Wide Web Foundation 
Licensed under the [European Union Public Licence](http://joinup.ec.europa.eu/software/page/eupl/licence-eupl)