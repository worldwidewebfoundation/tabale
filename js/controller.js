/*jslint devel: true, browser: true, maxerr: 1, indent: 2, undef: true */


var
  $, Recorder, Handlebars, view, I18N, tabaleWebLang; // externals


var VMeetup = (function () {
    "use strict";

    var
      modelApiUrl = "model/",
      messageTimeout,
      lastRefreshed = new Date(),
      idRegexp = new RegExp("([^_]+)_([^_]+)_([0-9]+)"),
      users, meetings, languages,
      userListTemplate, newUserFormLanguagesTemplate, newMeetingFormUsersTemplate, newMeetingFormLanguagesTemplate, meetingListTemplate, meetingParticipantsTemplate, meetingUserAddTemplate, editableTemplate, newMeetingFormCreatorTemplate,
      newMeetingLanguages = [],
      recorders = [],
      audioRecorder;

    Array.prototype.findById = function (id) {
      var result = this.filter(function (element, index, array) { return element.id == id; });
      if (result.length > 1) {
        throw "findInArrayById: array contains more than one element with given id";
      } else {
        if (result.length === 0) {
          return null;
        }
        return result[0];
      }
    };

    Array.prototype.replaceAtId = function (id, replacement) {
      var i;
      for (i = 0; i < this.length; i = i + 1) {
        if (this[i].id === id) {
          this[i] = replacement;
          break;
        }
      }
      if (i === this.length) {
        throw "replaceAtId. Index " + i + " not found in " + this;
      }
      return i;
    };

    function pad2(n) { return (n < 10 ? "0" + n : n.toString()); }
    function formatDateTime(y, m, d, h, minutes) {  return pad2(d) + "/" + pad2(m) + "/" + y + " " + pad2(h) + ":" + pad2(minutes); }

    // returns the object's id number from an HTML id string. Eg, getIdNumber("meeting_user_3") === 3
    String.prototype.getId = function () {
      var result = parseInt(this.substr(1 + this.lastIndexOf('_')), 10);
      if (isNaN(result)) {
        throw "getIdNumber returned NaN on " + this;
      }
      return result;
    };


    function error(text) {
      alert(text);
    }


    //######################################################################
    // get language data from API

    function get_languages(success, failure) {
      $.get(modelApiUrl + "?action=get_languages")
        .done(function (data, textStatus, jqXHR) {
          if (data.status === "OK") {
            languages = data.content;
            success();
          } else {
            languages = null;
            if (failure) {
              failure(data.message);
            } else {
              error("get_languages: API error retrieving list: " + data.message);
            }
          }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
          if (failure) {
            failure(textStatus);
          } else {
            error("get_languages: XHR error retrieving list: " + textStatus);
          }
        });
    }

    //####################################################################
    function new_meeting_form_update_languages(event) {
      // when a new user is added or removed from a meeting we need to
      // check what languages are available
      // recompute newMeetingLanguages from current list of participants;
      var languageAuthorized, newMeetingParticipantsIDs = [], participantLanguages, addWarningText;

      $(".new_meeting_user:checked").each(function (index, element) {
        newMeetingParticipantsIDs.push(parseInt($(element).attr("value"), 10));
      });
      newMeetingLanguages = [];
      languageAuthorized = false;

      if (newMeetingParticipantsIDs.length > 0) {
        $.each(languages, function (language_id, language) {
          languageAuthorized = true;
          $.each(newMeetingParticipantsIDs, function (index, newMeetingParticipantID) {
            // find what languages that participant speaks
            participantLanguages = users.findById(newMeetingParticipantID).languages;
            if (participantLanguages.findById(language_id) === undefined) {
              languageAuthorized = false;
              // can we break from a $.each?
            }
          });
          if (languageAuthorized) {
            newMeetingLanguages.push(language_id);
          }
        });

        addWarningText = false;
        $("#new_meeting_languages input.languageSelect").each(function (index, element) {
          if (!element.checked ||  $.inArray(element.value, newMeetingLanguages) !== -1) {
            $(element).parent().removeClass("new_meeting_language_warning").attr("title", "");
          } else {
            if (element.checked) {
              $(element).parent().addClass("new_meeting_language_warning").attr("title", "langWarning");
              addWarningText = true;
            }
          }
        });
      }
    }

    //####################################################################

    function updateLanguageList() {

      $("#new_meeting_languages_placeholder").html(newMeetingFormLanguagesTemplate({"languages": languages}));
      $("#new_user_languages_placeholder").html(newUserFormLanguagesTemplate({"languages": languages}));

      $.each(languages, function (languageID, language) {
        recorders[language.code] = new Recorder(language.code, {
          upload_url: modelApiUrl + "?action=upload_wav&amp;upload_dir=../media&from=flash",
          imageDir: "img",
          messages: I18N.recorderMessages[tabaleWebLang]
        });

        $("#recorder_" + language.code).on("recordingSaved", function () {
          $(this).addClass("uploaded");
          $(this).removeClass("notUploadedYet");
        });
      });


      $(".languageSelect").on('click', function () {
        if ($(this).is(":checked")) {
          $(this).parent().next("td").next("td").removeClass("hidden");
        } else {
          $(this).parent().next("td").next("td").addClass("hidden");
        }
        new_meeting_form_update_languages();
      });

      $('#new_meeting_languages_table :button').click(function () {
        var
          formData = new FormData(this.parentNode),
          thisLangRecorder = this.parentNode.parentNode,
          formId = this.id,
          languageCode = formId.substr(formId.lastIndexOf('_') + 1);
        $.ajax({
          url: modelApiUrl + '?action=upload_wav&upload_dir=../media&from=file&lang=' + languageCode,  //server script to process data
          type: 'POST',
          xhr: function () {  // custom xhr
            var myXhr = $.ajaxSettings.xhr();
//            if (myXhr.upload) { // check if upload property exists
//                  myXhr.upload.addEventListener('progress',progressHandlingFunction, false); // for handling the progress of the upload
//            }
            return myXhr;
          },
          //Ajax events
//          beforeSend: beforeSendHandler,
          success: function () {
            thisLangRecorder.setAttribute('class', 'uploaded');
          },
          error: function (e) {
            thisLangRecorder.setAttribute('class', 'notUploadedYet');
            alert("Erreur: " + e.responseText);
          },
          // Form data
          data: formData,
          //Options to tell JQuery not to process data or worry about content-type
          cache: false,
          contentType: false,
          processData: false
        });
      });

    }

    //####################################################################
    // get user data from API
    function get_users(success, failure) {
      $.get(modelApiUrl + "?action=get_users")
        .done(function (data, textStatus, jqXHR) {
          if (data.status === "OK") {
            users = data.content;
            success();
          } else {
            users = null;
            if (failure) {
              failure(data.message);
            } else {
              error("get_users: API error retrieving list: " + data.message);
            }
          }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
          if (failure) {
            failure(textStatus);
          } else {
            error("get_users: XHR error retrieving user list: " + textStatus);
          }
        });
    }

    //####################################################################

    // get meeting data from API

    function get_meetings(success, failure) {
      $.get(modelApiUrl + "?action=get_meetings")
        .done(function (data, textStatus, jqXHR) {
          if (data.status === "OK") {
            meetings = data.content;
            success();
          } else {
            meetings = null;
            if (failure) {
              failure(data.message);
            } else {
              error("get_meetings: API error retrieving list: " + data.message);
            }
          }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
          if (failure) {
            failure(textStatus);
          } else {
            error("get_meetings: XHR error retrieving user list: " + textStatus);
          }
        });
    }

    //###################################################################

    function change_participant_status(event) {
      var meetingUserId = event.target.id, newStatus = event.target.value;
      $.get(modelApiUrl + '?action=change_meeting_participation&initiator=operator&meeting_user_id=' + meetingUserId.substr(meetingUserId.lastIndexOf('_') + 1) + '&meeting_participation_status=' + newStatus)
        .fail(function () { error("Error while changing meeting participation"); })
        .always(function () { $(event.target).blur(); });
    }

    //###################################################################
    function updateMeetingParticipantList(meeting) {
      var meetingId = meeting.id, usersNotInMeeting = [], i, j, userParticipant;
      // create each list's dialog
      // first we need to work around a limitation of handlebar.js
      $.each(meeting.participants, function (index, participant) {
        participant.isComing = participant.status === "yes";
        participant.isNotComing = participant.status === "no";
        participant.isMaybeComing = participant.status === "maybe";
        participant.unknownComing = participant.status === "unknown";
      });
      // then we make the markup from the template
      $("#meeting_participants_tbody_" + meetingId).html(meetingParticipantsTemplate(meeting));

      // TODO: this should not be here but inline in the HTML
//        $("#meeting_participants_table_" + meetingId).tablesorter({headers: { 1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}}});

      // the we also update the add meeting participant control
      // find the list of participants not already in the meeting
      for (i = users.length - 1; i >= 0; i = i - 1) {
        for (j = meeting.participants.length - 1; j >= 0; j = j - 1) {
          if (meeting.participants[j].userId === users[i].id) {
            break;
          }
        }
        if (j < 0) {
          usersNotInMeeting.push(users[i]);
        }
      }
      $("#meeting_user_add_" + meetingId).replaceWith(meetingUserAddTemplate({"users": usersNotInMeeting, "meetingId": meetingId}));
    }

    //###################################################################
    function remove_participant(event) {
      var
        meetingUserIdString = event.target.id,
        meetingUserID =  meetingUserIdString.substr(1 + meetingUserIdString.lastIndexOf("_"));

      $.post(modelApiUrl, { "action" : "delete_meeting_user", "id" : meetingUserID })
        .fail(function (jqXHR, textStatus, errorThrown) { error("error removing participant: " + errorThrown); })
        .done(function (data) {
          $(event.target).parent().parent().remove(); // remove table row
          meetings.replaceAtId(data.content.id, data.content); // update meeting list from API return
          updateMeetingParticipantList(data.content);
        });
    }

    //###################################################################
    function call_participant(event) {
      var meetingUserId = event.target.id, user_name;
      meetingUserId = meetingUserId.substr(1 + meetingUserId.lastIndexOf('_'));
      user_name = $(event.target).parent().prev().prev().prev().prev().text();
      if (view.confirm("callConfirm", user_name, "?")) {
        view.message("calling " + user_name, 5000);
        $.post(modelApiUrl,
               {"action" : "request_participation",
                "meeting_user_id" : meetingUserId})
          .fail(function (jqXHR, textStatus, errorThrown) {
            alert("erreur: " + jqXHR.responseText);
          });
      }
    }

    //###################################################################


    function updateMeetingParticipantLists() {
      $(".dialog").dialog("destroy");
      $("#participants_placeholder").html("");

      $.each(meetings, function (index, meeting) {
        var meetingId = meeting.id, usersNotInMeeting = [], i, j, userParticipant;
        // create each list's dialog
        // first we need to work around a limitation of handlebar.js
        $.each(meeting.participants, function (index, participant) {
          participant.isComing = participant.status === "yes";
          participant.isNotComing = participant.status === "no";
          participant.isMaybeComing = participant.status === "maybe";
          participant.unknownComing = participant.status === "unknown";
        });

        // find the list of participants not already in the meeting
        for (i = users.length - 1; i >= 0; i = i - 1) {
          for (j = meeting.participants.length - 1; j >= 0; j = j - 1) {
            if (meeting.participants[j].userId === users[i].id) {
              break;
            }
          }
          if (j < 0) {
            usersNotInMeeting.push(users[i]);
          }
        }

        $("#participants_placeholder").append(view.createParticipationControl(meetingId, meetingParticipantsTemplate(meeting), meetingUserAddTemplate({"users": usersNotInMeeting, "meetingId": meetingId})));

        // TODO: this should not be here but inline in the HTML
        $("#meeting_participants_table_" + meetingId).tablesorter({headers: { 1: {sorter: false}, 2: {sorter: false}, 3: {sorter: false}}});

      });

      $(".dialog").dialog({modal: true, autoOpen: false, height: 600, width: 600,
                           position: {my: "center", at: "top", of: window}});
    }

    //###################################################################

    function updateUsersTable() {
      $("#users_placeholder").html(userListTemplate({"users": users}));
      $("#users_table").trigger("update"); // let the tablesorter know that we made a update
      $(".new_meeting_user").click(new_meeting_form_update_languages);
      $("#users_table_and_buttons").css("display", "block");
    }

    //###################################################################

    function edit_cell_callback(event) {
      var $cell, matches, node_id, object_name, field_name, oldVal;
      $cell = $(event.target);
      matches = idRegexp.exec($cell.attr("id"));
      node_id = parseInt(matches[3], 10);
      object_name = matches[1];
      field_name = matches[2];
      oldVal = $cell.text();

      // don't let the user click while editing
      $("body").off('click', '.editable');

      switch (field_name) {
      case 'datetime':
        break; // TODO
      case 'name':
      case 'phone':
      case 'title':
      case 'location':
        $cell.html(editableTemplate(oldVal));
        break;
      default:
        error("edit_cell_callback: unknown field name " + field_name);
      }

      $("#editing")
        .focus()
        .focusout(function () {
          $cell.html(oldVal);
          $("body").on('click', '.editable', edit_cell_callback);
        })
        .keyup(function (event) {
          if (event.keyCode === 27) {
            $(this).focusout();
          }
          if (event.keyCode === 13) {
            var newVal = $(this).val();
            $.post(modelApiUrl, {
              "action" : object_name === "user" ? "change_user" : "change_meeting",
              "field_to_change" : field_name,
              "id" : node_id,
              "value" : newVal
            })
              .done(function (data) {
                $cell.html(newVal);
                if (object_name === "user") {
                  users.replaceAtId(node_id, data.content);
                  updateMeetingParticipantLists();
                } else {
                  meetings.replaceAtId(node_id, data.content);
                  updateUsersTable();
                }
              })
              .fail(function (jqXHR, textStatus, errorThrown) { error("error: " + errorThrown); $cell.html(oldVal); })
              .always(function () { $("body").on('click', '.editable', edit_cell_callback); });
          }
        });
    }


    //######################################################################

    function updateUserLog() {
      $.get("userlog.txt",
        function (data) {
          var lines = data.split("\n"), i, reversedLines = "";
          for (i = lines.length - 1; i >= 0; i = i - 1) {
            if (lines[i].length > 0) {
              reversedLines += lines[i];
            }
          }
          $(document.getElementById("userlog")).html(reversedLines);
        });
    }


    //######################################################################
    // Normally this should only be called once, when the page is loaded
    function getAllDataFromServer(params) {
      get_meetings(function () {
        get_users(function () {
          get_languages(params.success,
            function () {
              error("Error retrieving language list");
              params.failure();
            });
        },
          function () {
            error("Error retrieving user list");
            params.failure();
          });
      },
        function () {
          error("Error retrieving meeting list");
          params.failure();
        });
    }


    //######################################################################
    // refresh everything -- in principle should only be called once on page load,
    // but we call it anyway for easier update of meeting and participant list when meetings
    // or users are deleted

    function refresh() {
      view.message("please_wait");

      getAllDataFromServer({
        success: function () {
          updateLanguageList();

          meetings = meetings.sort(function(a,b) { return b.id - a.id; });
          users = users.sort(function(a, b) {
            if (a.name.toLowerCase() < b.name.toLowerCase()) return -1;
            if (a.name.toLowerCase() > b.name.toLowerCase()) return 1;
            return 0;
          });

          $("#meetings_placeholder").html(meetingListTemplate({"meetings": meetings}));
          $("#new_meeting_form_users_placeholder").html(newMeetingFormUsersTemplate({"users": users}));
          $("#new_meeting_creator_placeholder").html(newMeetingFormCreatorTemplate({"admins": users}));
          updateMeetingParticipantLists();

          updateUsersTable();
          updateUserLog();
          view.clearMessage();
        },
        failure: function () {
          error("getAllDataFromServer failed");
        }
      });
    }

    //######################################################################

    function call_undecided_callback(event) {
      var
        meeting_id = event.target.id.getId();

      get_meetings(function () {
        var
          meeting = meetings.findById(meeting_id),
          users_to_call = {}, names_to_call = [];
        $.each(meeting.participants, function (meeting_user_id, meeting_user) {
          if (meeting_user.status === "unknown") {
            users_to_call[meeting_user_id] = meeting_user;
          }
        });

        if (view.confirm("callConfirm")) {
          $.each(users_to_call, function (meeting_user_id, meeting_user) {
            $.post(modelApiUrl,
              {"action" : "request_participation",
                "meeting_user_id" : meeting_user.participantId})
              .fail(function (jqXHR, textStatus, errorThrown) { error("error calling_user: " + errorThrown + ", " + textStatus); });
          });
        }
      });
    }

    //######################################################################

    function meeting_user_add_callback(event) {
      var
        meetingId = parseInt(event.target.id.getId(), 10),
        selected = $(event.target).val(), selectedUserId, selectedUser, selectedUserLanguages, meetingLanguages, meetingLanguage, userLanguages, userLanguage, found = false, meeting;
      if (selected !== 'select') {
        selectedUser = users.findById(selected.getId());

        // first, we need to check if the user speaks one of the languages set for the meeting
        // walk through each language of the meeting, and if it's not spoken by the user issue a warning

        meeting = meetings.findById(meetingId);
        meetingLanguages = meeting.languages;
        userLanguages = selectedUser.languages;

        for (meetingLanguage in meetingLanguages) {
          if (meetingLanguages.hasOwnProperty(meetingLanguage)) {
            for (userLanguage in userLanguages) {
              if (userLanguages.hasOwnProperty(userLanguage)) {
                if (userLanguages[userLanguage].code === meetingLanguages[meetingLanguage].code) {
                  found = true;
                }
              }
            }
          }
        }

        if (found === false) {
          view.alert("userSpeaksNone");
          updateMeetingParticipantLists();
        } else {

          // first we must create a new meeting_user
          $.post(modelApiUrl, {"action" : "add_meeting_user", "meeting_id" : meetingId, "user_id" : selectedUser.id})
            .done(function (response) {
              // we've successfully added the new participant to the meeting
              // - update the list of meetings
              meetings.replaceAtId(meetingId, response.content);
              // update the views
              updateMeetingParticipantList(response.content);
              updateUsersTable();
              updateUserLog();
            })
            .fail(function (jqXHR, textStatus, errorThrown) { error("error: " + errorThrown);  });
//          $(event.target).val("select"); // reset dropdown
        }
      }
    }

    //######################################################################

    function language_meeting_button_callback(event) {
      var
        languageMeetingId = event.target.id.substr(event.target.id.lastIndexOf('_') + 1),
        $languageMeetingSelect = $(event.target),
        selectedOption = $languageMeetingSelect.find("option:selected").val();

      if (selectedOption === "remove") {
        $.post(modelApiUrl, { "action" : "delete_language_meeting", "id" : languageMeetingId })
          .done(function (data) {
            $languageMeetingSelect.remove();
          })
          .fail(function (jqXHR, textStatus, errorThrown) { error("error: " + errorThrown);  });
      }
    }

    //######################################################################

    function delete_meetings_callback() {
      var meetingsToDelete = [];
      // retrieve list of selected meetings
      $("#meetings tr td input[type='checkbox']:checked").each(function () {
        meetingsToDelete.push(this.value);
      });
      if (meetingsToDelete.length > 0) {
        $.post(modelApiUrl,
               {"action" : "delete_meeting", "select_meeting" : meetingsToDelete.toString()})
          .done(function (data) { refresh(); view.message("meetingsDeleted"); })
          .fail(function (data) { error("errors deleting meetings"); });
      }
    }

    //######################################################################

    function delete_users_callback() {
      var usersToDelete = [];
      // retrieve list of selected users
      $("#users tr td input[type='checkbox']:checked").each(function () {
        usersToDelete.push(this.value);
      });
      if (usersToDelete.length > 0) {
        $.post(modelApiUrl,
               {"action" : "delete_user", "select_user" : usersToDelete.toString()})
          .done(function (data) { refresh(); })
          .fail(function (data) { error("error deleting people"); });
      }
    }

    //######################################################################

    function add_user() {
      var idstring, userLanguages = [];

      $("#newUserSortedLanguages li").each(function (index, item) {
        userLanguages.push(item.getAttribute("id").getId());
      });

      view.message("addingUser");
      $.post(modelApiUrl,
             {"action" : "add_user",
              "name" : $(" #new_user_name").val(),
              "phone" : $("#new_user_phone").val(),
              "include_language[]" : userLanguages})
        .done(function (data) {
          $("#new_user_name, #new_user_phone").val("");
          $("#new_user_languages").html("");
          $("#newUserSortedLanguages li").remove();
          refresh();
          view.message("new_user_added", 5000);
        })
        .fail(function (data) {
          view.message("new_user_added_error", ": ", data.responseText);
        });
    }

    //######################################################################

    function add_meeting() {
      var
        meetingLanguages = [],
        meetingUsers = [],
        meetingTitle = $("#new_meeting_title").val(),
        meetingLocation = $("#new_meeting_location").val(),
        meetingStartTime = $("#new_meeting_start_datetime").val(),
        meetingEndTime = $("#new_meeting_end_datetime").val(),
        meetingCreatorId = $("#new_meeting_creator").val(),
        missingRecording = false;

      if (meetingCreatorId === "0") { view.alert("errorNoMeetingCreator"); return; }
      if (meetingTitle === "") { view.alert("errorNoMeetingTitle"); return; }
      if (meetingLocation === "") { view.alert("errorNoMeetingLocation"); return; }
      if (meetingStartTime === "") { view.alert("errorNoMeetingStartTime"); return; }
      if (meetingEndTime === "") { view.alert("errorNoMeetingEndTime"); return; }

      $("#new_meeting_languages_table input:checked").each(function () {
        var
          languageId = parseInt($(this).val(), 10),
          languageCode = languages.findById(languageId).code;
        if (!$(this).parent().next().next().hasClass('uploaded')) {
          view.alert("noRecordingForLanguage", languageCode);
          missingRecording = true;
        }
        meetingLanguages.push(languageId);
      });
      if (missingRecording) { return; }

      $("#new_meeting_form_users_placeholder input:checked").each(function () { meetingUsers.push(this.value); });

      if (meetingLanguages.length === 0) {
        view.alert("noAnnouncementRecorded");
        return;
      }

      if (meetingUsers.length === 0) {
        view.alert("pleaseSelectUsers");
        return;
      }
      if (meetingLanguages.length === 0) {
        view.alert("pleaseSelectLanguages");
        return;
      }
      $.post(modelApiUrl,
             {"action" : "add_meeting",
              "title" : meetingTitle,
              "creatorId" : meetingCreatorId,
              "location" : meetingLocation,
              "start_datetime" : meetingStartTime,
              "end_datetime" : meetingEndTime,
              "include_language[]" : meetingLanguages,
              "include_user[]" : meetingUsers})
        .done(function (data) {
          $("#new_meeting_title, #new_meeting_location, #new_meeting_start_datetime, #new_meeting_end_datetime").val("");
          $("#new_meeting_languages, #new_meeting_participants").html("");
          refresh();
          $("#tabs").tabs({active: 0});
          view.message("meeting added");
        })
        .fail(function (jqXHR, textStatus, errorThrown) { error("error adding meeting: " + errorThrown + ", " + textStatus); });
      view.message("adding meeting");
    }


    function clear_log_callback() {
      // clear the log displayed in the Log tab
      if (view.confirm("confirmClearLog")) {
        $.post(modelApiUrl, {"action" : "clear_user_log"})
          .done(function () {
            $(document.getElementById("userlog")).html("");
          })
          .fail(function () { error("Error while trying to clear log"); });
      }
    }

    // when a unknown telephone number is called, offer to create a new user
    function prepare_new_user_callback(event) {
      var phoneNumber;
      if (view.confirm("newNumberCreate")) {
        phoneNumber = $(event.target).text();
        $("#tabs").tabs({active: 1});
        $("#new_user_phone").val(phoneNumber);
        $("#new_user_name").focus();
      }
      return false;
    }


    $(document).ready(function () {

      // all the handlebards stuff should be in the view eventually
      userListTemplate = Handlebars.compile($("#users-template").html());
      newMeetingFormUsersTemplate = Handlebars.compile($("#newMeetingFormUsersTemplate").html());
      meetingListTemplate = Handlebars.compile($("#meetings-template").html());
      meetingParticipantsTemplate = Handlebars.compile($("#meetingParticipantsTemplate").html());
      newMeetingFormLanguagesTemplate = Handlebars.compile($("#newMeetingFormLanguagesTemplate").html());
      newMeetingFormCreatorTemplate = Handlebars.compile($("#newMeetingFormCreatorTemplate").html());
      newUserFormLanguagesTemplate = Handlebars.compile($("#newUserFormLanguagesTemplate").html());
      meetingUserAddTemplate = Handlebars.compile($("#meetingUserAddTemplate").html());
      editableTemplate = Handlebars.compile($("#editableTemplate").html());

      // template helpers

      Handlebars.registerHelper('languageName', function (id) {
        return I18N.languageNames[view.lang][languages.findById(id).code];
      });
      Handlebars.registerHelper('userName', function (id) {
        var userName;
        try {
          userName = users.findById(id).name;
        } catch (error) {
          return "?";
        }
        return users.findById(id).name;
      });
      Handlebars.registerHelper('meetingTitle', function (id) {
        return meetings.findById(id).title;
      });
      Handlebars.registerHelper('formattedStartDateTime', function (meeting) {
        return pad2(meeting.start_day) + "/" + pad2(meeting.start_month) + "/" + meeting.start_year + " " + pad2(meeting.start_hours) + ":" + pad2(meeting.start_minutes);
      });
      Handlebars.registerHelper('formattedEndDateTime', function (meeting) {
        return pad2(meeting.end_day) + "/" + pad2(meeting.end_month) + "/" + meeting.end_year + " " + pad2(meeting.end_hours) + ":" + pad2(meeting.end_minutes);
      });


      refresh();



      $("#meetings_table").tablesorter({headers: { 0: {sorter: false},
                                                   5: {sorter: false},
                                                   6: {sorter: false},
                                                   7: {sorter: false}}});
      $("#users_table").tablesorter({headers: { 0: {sorter: false},
                                                3: {sorter: false},
                                                4: {sorter: false}}});

      $("#add_user_button").click(add_user);
      $("#add_meeting_button").click(add_meeting);
      $('#clear_log_button').click(clear_log_callback);


//      $("#recorder_en").on("change", function () { alert("YES"); });



      // event callbacks
      $('#userlog').
        on("click", "a.newPhoneNumber", prepare_new_user_callback);

      $("body").
        on('change', ".participant_status", change_participant_status).
        on('change', ".meeting_user_add",   meeting_user_add_callback).
        on('click',  ".participant_remove", remove_participant).
        on('click',  ".participant_call", call_participant).
        on('click',  ".call_undecided", call_undecided_callback).
        on('click',  ".editable", edit_cell_callback);


      $("#meetings_placeholder").
        on("click", "#delete_meetings_button", delete_meetings_callback);

      $("#users_placeholder").
        on("click", "#delete_users_button", delete_users_callback);

    });

  }());
