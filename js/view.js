/*jslint devel: true, browser: true, maxerr: 1, indent: 2, undef: true */

var $;

var view = {};

view.setup = function (lang, localizedStrings) {
  "use strict";
  this.lang = lang;
  this.g_localizedStrings = localizedStrings;
  this.g_messageTimeout = null;
};

view.message = function (code, arg2, arg3, arg4) {
  "use strict";

  var s = this.g_localizedStrings[code], timeout;

  if (typeof arg4 === "number") {
    timeout = arg4;
    s+=arg2+arg3;
  } else {
    if (typeof arg3 === "number") {
      timeout = arg3;
      s+=arg2;
    } else {
      if (typeof arg2 === "number") {
        timeout = arg2;
      } else {
        timeout = 999999;
      }
    }
  }

  window.clearTimeout(this.g_messageTimeout);
  $("#message")
    .text(s)
    .css("display", "block");
  this.g_messageTimeout = window.setTimeout(function () {$("#message").css("display", "none"); }, timeout);
};

view.clearMessage = function () {
  "use strict";
  window.clearTimeout(this.g_messageTimeout);
  $("#message").css("display", "none");
};

view.confirm = function (code, arg1, arg2, arg3) {
  "use strict";
  return confirm(this.g_localizedStrings[code] + (arg1||"") + (arg2||"") + (arg3||""));
};

view.alert = function (code, arg1, arg2, arg3) {
  "use strict";
  alert(this.g_localizedStrings[code] + (arg1||"") + (arg2||"") + (arg3||""));
};

/* normally, this should be a handlebars template. However it doesn't let templates inside templates */
view.createParticipationControl = function (meetingId, insideMarkup, markupAddUser) {
  "use strict";
  return "<div class='dialog' title='" + this.g_localizedStrings.participants + "' id='meeting_participants_" + meetingId + "'>" +
    "<h4><?php echo s('refresh_to_update') ?></h4>" +
    "<table class='tablesorter' id='meeting_participants_table_" + meetingId + "'>" +
    "  <thead>" +
    "    <tr>" +
    "      <th>" + this.g_localizedStrings.name + "</th>" +
    "      <th>" + this.g_localizedStrings.participation + "</th>" +
    "      <th>" + this.g_localizedStrings.message + "</th>" +
    "      <th>" + this.g_localizedStrings.nbTimesCalled + "</th>" +
    "      <th>" + this.g_localizedStrings.actions + "</th>" +
    "    </tr>" +
    "  </thead>" +
    "  <tbody id='meeting_participants_tbody_" + meetingId + "'>" + insideMarkup + "  </tbody>" +
    "</table>" +
    markupAddUser + "<br/>" +
    "<input type='button' class='call_undecided' id='call_undecided_" + meetingId + "' value='" + this.g_localizedStrings.call_undecided + "'/><br/>" +
    "<input type='button' id='close_" + meetingId + "' onclick='$(\"#meeting_participants_" + meetingId + "\").dialog(\"close\")' value='" + this.g_localizedStrings.close + "' /><br/>" +
    "  </div>";
};
