/*jslint devel: true, browser: true, maxerr: 50, indent: 2 */

var $, swfobject;

var Recorder = function (anchor, params) {
  "use strict";
  var
    that = this,
    appWidth = 24,
    appHeight = 24,
    flashvars, attributes, g, markup, flashCallbackName;

  this.saved = false;
  this.anchor = anchor;
  this.recorder = null;
  this.recorderOriginalWidth = 0;
  this.recorderOriginalHeight = 0;
  this.uploadFormId = null;
  this.uploadFieldName = null;

  this.connect = function (name, attempts) {
    var frm;
    if (navigator.appName.indexOf("Microsoft") !== -1) {
      this.recorder = window[name];
    } else {
      this.recorder = document[name];
    }

    if (attempts >= 40) {
      return;
    }

    // flash app needs time to load and initialize
    if (this.recorder && this.recorder.init) {
      this.recorderOriginalWidth = this.recorder.width;
      this.recorderOriginalHeight = this.recorder.height;
      if (this.uploadFormId) {
        frm = $(this.uploadFormId);
        this.recorder.init(frm.attr('action').toString(), this.uploadFieldName, frm.serializeArray());
      }
      return;
    }
    setTimeout(function () {that.connect(name, attempts + 1); }, 100);
  };

  this.playBack = function (name) {
    this.recorder.playBack(name);
  };

  this.record = function (name, filename) {
    this.recorder.record(name, filename);
  };

  this.resize = function (width, height) {
    this.recorder.width = width + "px";
    this.recorder.height = height + "px";
  };

  this.defaultSize = function (width, height) {
    this.resize(this.recorderOriginalWidth, this.recorderOriginalHeight);
  };

  this.show = function () {
    this.recorder.show();
  };

  this.hide = function () {
    this.recorder.hide();
  };

  this.updateForm = function () {
    var frm = $(this.uploadFormId);
    this.recorder.update(frm.serializeArray());
  };

  this.showPermissionWindow = function () {
    this.resize(240, 160);
    // need to wait until app is resized before displaying permissions screen
    setTimeout(function () { that.recorder.permit(); }, 1);
  };

  this.microphone_recorder_events = function () {
    var
      args = arguments,
      eventName = args[0],
      width, height, name, mic, duration, latency, data, bytesLoaded, bytesTotal,
      errorMessage;

    switch (eventName) {
    case "ready":
      width = parseInt(args[1], 10);
      height = parseInt(args[2], 10);
      that.uploadFormId = "#uploadForm_" + anchor;
      that.uploadFieldName = "uploadfile[filename]";
      that.connect("recorderApp_" + anchor, 0);
      that.recorderOriginalWidth = width;
      that.recorderOriginalHeight = height;
//      $('#play_button_' + anchor).css({'margin-left': width + 8});
      $('#save_button_' + anchor).css({'width': width, 'height': height});
      $('#status_' + anchor).css({'color': '#000'}).text(params.messages.readyToRecord);
      break;

    case "no_microphone_found":
      break;

    case "microphone_user_request":
      that.showPermissionWindow();
      break;

    case "microphone_connected":
      mic = args[1];
      that.defaultSize();
      $('#status_' + anchor).css({'color': '#000'}).text(params.messages.readyToRecord);
      break;

    case "microphone_not_connected":
      that.defaultSize();
      $('#status_' + anchor).css({'color': '#000'}).text(params.messages.noMicrophone);
      break;

    case "microphone_activity":
//      $('#status_' + anchor).text(args[1]);
      break;

    case "recording":
      name = args[1];
      that.hide();
      $('#record_button_' + anchor + ' img').attr('src', params.imageDir + '/stop.png');
      $('#play_button_' + anchor).hide();
      $('#status_' + anchor).css({'color': '#000'}).text(params.messages.talkNow);

      break;

    case "recording_stopped":
      name = args[1];
      duration = args[2];
      that.show();
      $('#record_button_' + anchor + ' img').attr('src', params.imageDir + '/record.png');
      $('#play_button_' + anchor).show();
      that.saved = false;
      $('#status_' + anchor).css({'color': 'black'}).text(params.messages.saveRecording);


      break;

    case "playing":
      name = args[1];
      $('#record_button_' + anchor + ' img').attr('src', params.imageDir + '/record.png');
      $('#play_button_' + anchor + ' img').attr('src', params.imageDir + '/stop.png');
      break;

    case "playback_started":
      name = args[1];
      latency = args[2];
      break;

    case "stopped":
      name = args[1];
      $('#record_button_' + anchor + ' img').attr('src', params.imageDir + '/record.png');
      $('#play_button_' + anchor + ' img').attr('src', params.imageDir + '/play.png');

      break;

    case "save_pressed":
      $('#status_' + anchor).css({'color': 'black'}).text(params.messages.recording);
      that.updateForm();
      break;

    case "saving":
      name = args[1];
      break;

    case "saved":
      name = args[1];

//      data = $.parseJSON(args[2]);
//      if (data.status === "OK") {
      $('#status_' + anchor).css({'color': 'black'}).text(params.messages.success);
      $('#control_panel_' + anchor).parent().trigger('recordingSaved');
      that.saved = true;
//      } else {
//        $('#status_' + anchor).css({'color': '#F00'}).text("ERREUR!");
//        that.saved = false;
//      }
      break;

    case "save_failed":
      name = args[1];
      errorMessage = args[2];
      $('#status_' + anchor).css({'color': '#F00'}).text(name + " ERROR: " + errorMessage);
      break;

    case "save_progress":
      name = args[1];
      bytesLoaded = args[2];
      bytesTotal = args[3];
      $('#status_' + anchor).css({'color': '#000'}).text(name + bytesLoaded + " / " + bytesTotal);
      break;
    }
  };


  // insert the markup
  markup = "<div class='recorder_control_panel' id='control_panel_" + anchor + "'><a href='javascript:void(0);' id='record_button_" + anchor + "'><img src='" + params.imageDir + "/record.png' alt=''/></a>" +


    "<a href='javascript:void(0);' class='recorder_play_button' id='play_button_" + anchor + "'><img src='" + params.imageDir + "/play.png' alt=''/></a>" +

    "<span class='recorder_save_button' id='save_button_" + anchor + "'><span id='flashcontent_" + anchor + "'>Your browser must have JavaScript enabled and the Adobe Flash Player installed.</span></span>" +

//    "<span id='upload_status_" + anchor + "'></span>" +

    "<span id='status_" + anchor + "'></span>" +

//    "<span id='activity_level_" + anchor + "'></span>" +

    "<form style='display:none' id='uploadForm_" + anchor + "' name='uploadForm_" + anchor + "' action='" + params.upload_url + "'><input name='authenticity_token' value='xxxxx' type='hidden'/><input name='upload_file[parent_id]' value='1' type='hidden'/><input name='format' value='json' type='hidden'/></form>";

  $("#recorder_" + anchor).append(markup);

  flashCallbackName = 'microphone_recorder_events_' + anchor;
  window[flashCallbackName] = this.microphone_recorder_events;

  attributes = {'id': "recorderApp_" + anchor, 'name':  "recorderApp_" + anchor};
  flashvars = {'event_handler': 'microphone_recorder_events_' + anchor,
               'upload_image': params.imageDir + '/upload.png'};

  swfobject.embedSWF("recorder.swf", "flashcontent_" + anchor, appWidth, appHeight, "10.1.0", "", flashvars, {}, attributes);


  $("#record_button_" + anchor).click(function () {
  // the first argument is internal to this file and is for playback
  // the second argument is the name of the file to save on the server
    that.record('audio', 'audio_' + anchor + '.wav');
  });

  $("#play_button_" + anchor).click(function () {
    that.playBack('audio');
  });

};
