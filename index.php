<?php 
require_once("i18n.php");
require_once("passwords.php");

if (isset($_GET['lang']))
  $lang = $_GET['lang'];
else
  $lang = "fr";


function s($message) {
  global $lang;
  return I18N::s($lang,$message);
}
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Tabale</title>

  <script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
  <script type="text/javascript" src="js/handlebars.js"></script>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

  <script type="text/javascript" src="tablesorter/jquery.tablesorter.js"></script>
  <script type="text/javascript" src="js/jquery-ui-1.10.1.custom.min.js"></script>
  <script type="text/javascript" src="js/jquery.jeditable.mini.js"></script>
  <script type="text/javascript" src="js/jquery.ui.datetime.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

  <link type="text/css" rel="stylesheet" href="css/ui-lightness/jquery-ui-1.8.14.custom.css"/>
  <link rel="stylesheet" type="text/css" href="tablesorter/style.css"/>
  <link type="text/css" rel="stylesheet" href="css/recorder.css"/>
  <link type="text/css" rel="stylesheet" href="css/main.css"/>
</head>

<body>

<h1>Tabale</h1>


<p><img id="logo" src="img/logo.png" alt="logo"/></p>

<div id="message" style="display:none"></div>

<p id="lang"><a id="lang-fr" href="./?lang=fr">Français</a> <a id="lang-en" href="./?lang=en">English</a></p>

<script type="text/javascript">

  tabaleWebLang = "<?php echo $lang?>";

	$(function() {
		$( "#tabs" ).tabs();
        $( "#newUserSortedLanguages" ).sortable();
        $( "#newUserSortedLanguages" ).disableSelection();

        $("#new_user_languages_placeholder").on("click", "input:checkbox", function(event) {
          var langId = event.target.value, langName = $(event.target).next("span").text();
          if (event.target.checked) {
            if ($("#newUserSortedLanguages li[id=sorted_lang_"+langId+"]").length === 0) {
              $("#newUserSortedLanguages").append('<li id="sorted_lang_'+langId+'" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>'+langName+'</li>');
            }
          } else {
            $("#newUserSortedLanguages li[id=sorted_lang_"+langId+"]").remove();
          }
        });
	});
</script>

<div id="tabs">

<ul>
	<li><a href="#tabs-meetings"><?php echo s("events")?></a></li>
	<li><a href="#tabs-users"><?php echo s("Contacts")?></a></li>
	<li><a href="#tabs-log"><?php echo s("log")?></a></li>
	<li><a href="#tabs-add-meeting"><?php echo s("add-event")?></a></li>
	<li><a href="#tabs-add-users"><?php echo s("Ajouter un contact")?></a></li>
</ul>
<!-- # TABS ############################################################### -->
<div id="tabs-meetings">
  <h2><?php echo s("events")?></h2>
  <div id="meetings_placeholder"></div>
  <div id="participants_placeholder"></div>
</div>

<!-- ################################################################ -->
<div id="tabs-users">
  <h2><?php echo s("contacts")?></h2>
  <div id="users_placeholder"></div>
</div>

<!-- ################################################################ -->
<div id="tabs-log">
  <h2><?php echo s("log")?></h2>  
  <button id="clear_log_button"><?php echo s("effacer_liste")?></button>
  <div id="userlog">
  </div>
</div>


<div id="tabs-add-meeting">
  <h2><?php echo s("add-event")?></h2>

  <!-- yes, a table to do layout. Boo. -->
  <table id="new_meeting_table">
    <tr>
      <td>
        <form id="new_meeting" action="">
        <div>
        <label><?php echo s("created-by")?>: </label><div id="new_meeting_creator_placeholder"></div><br/>
        <label><?php echo s("title")?>: </label><input id="new_meeting_title" type="text" size="40" name="title" /><br/>
        <label><?php echo s("Lieu")?>: </label><textarea id="new_meeting_location" cols="40" rows="5" name="location" ></textarea><br/>
        <label><?php echo s("start_datetime")?>: </label><input type="text" id="new_meeting_start_datetime" readonly/><br/>
        <label><?php echo s("end_datetime")?>: </label><input type="text" id="new_meeting_end_datetime" readonly/><br/>
      
        <label><?php echo s("Participants")?>: </label><input type="button" value="<?php echo s("Sélectionner les participants")?>" onclick="$('#selectParticipantsPopup').dialog({modal:true, width: 600, height: 600})"/><br/>
        <br/>
      
        <input id="add_meeting_button" type="button" class="add_button" value="<?php echo s("create-event")?>"/>
        </div>
        </form>
      </td>
      <td>
        <h3><?php echo s("Langues")?>:</h3>
        <div><small><?php echo s("recordingInstructions")?></small></div>
        <div style="float:right; width:500px;" id="new_meeting_languages_placeholder"></div>
      </td>
    </tr>
  </table>
</div>

<div id="tabs-add-users">
  <h2><?php echo s("Ajouter un nouveau contact")?></h2>
  <div>
    <span><?php echo s("Nom")?>: </span><input id="new_user_name" type="text" name="name" /><br/>
    <span><?php echo s("Numéro de téléphone")?>: </span><input id="new_user_phone" type="text" name="phone" /><br/>
    <span><?php echo s("spokenLanguages")?>:</span><br/>
    <ul id="newUserSortedLanguages" class="droptrue"></ul>
    <span><?php echo s("add")?>:</span><span id="new_user_languages_placeholder"></span>
    
    <script type="text/javascript">
     function addLang(id) {
         $("#newUserSortedLanguages").append('<li class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' + id + '</li>');
     }
    </script>      
    <br/><input id="add_user_button" type="button" class="add_button" value="<?php echo s("Ajouter le contact")?>"/>
  </div>
</div>

</div>

<p><small>Created by the World Wide Web Foundation, see the <a href="notes.html">technical notes</a>. $Rev: 662 $</small></p>

</div>

<!--####### templates #####-->

<!-- template: meeting list -->
<script id="meetings-template" type="text/x-handlebars">
  {{#if meetings}}
  <p><small><?php echo s("instructions") . $inboundPhoneNumber?></small></p>
  <p><small><?php echo s("instructions2")?></small></p>
  <table id="meetings_table" class="tablesorter">
    <thead>
      <tr>
        <th>☑</th>
        <th> <?php echo s("Titre")?> </th>
        <th> <?php echo s("start_datetime")?> </th>
        <th> <?php echo s("end_datetime")?> </th>
        <th> <?php echo s("Lieu")?> </th>
        <th> <?php echo s("Participants")?> </th>
        <th> <?php echo s("announcements")?> </th>
        <th> <?php echo s("created-by")?> </th>
      </tr>
    </thead>
    <tbody id="meetings">
      {{#each meetings}}  
        <tr id='meeting_{{id}}'>
          <td><input type='checkbox' value='{{id}}'/></td>
          <td><span class="editable main-column" id="meeting_title_{{id}}">{{title}}</span></td>
          <td><span class='editable' id='meeting_start_datetime_{{id}}'>{{formattedStartDateTime this}}</span><input id='meeting_start_datetimepicker_{{id}}' type='text' style='visibility:hidden' size='1'/></td>
          <td><span class='editable' id='meeting_end_datetime_{{id}}'>{{formattedEndDateTime this}}</span><input id='meeting_end_datetimepicker_{{id}}' type='text' style='visibility:hidden' size='1'/></td>
          <td><span class="editable" id="meeting_location_{{id}}">{{location}}</span></td>
          <td>
            <b><?php echo s("total") ?>: {{nbParticipants}}</b><hr/>
            <?php echo s("total_coming") ?>: {{nbParticipantsComing}}<br/>
            <?php echo s("total_not_coming") ?>: {{nbParticipantsNotComing}}<br/>
            <?php echo s("total_maybe_coming") ?>: {{nbParticipantsMaybeComing}}<br/>
            <?php echo s("total_unconfirmed") ?>: {{nbParticipantsUnknown}}<br/>
            <input type='button' value='<?php echo s("displayParticipants") ?>' onclick='$("#meeting_participants_{{id}}").dialog("open")'/>
          </td>
          <td>
            <ul class="meeting_languages">
            {{#each languages}}
              <li> <a href="{{audioUrl}}" target="_blank">{{languageName id}}</li>
            {{/each}}
            </ul>
          </td>
          <td><span id="meeting_creator_{{id}}">{{userName creatorId}}</span></td>
        </tr>
      {{/each}}
    </tbody>
  </table>
  <input id="delete_meetings_button" type="button" value="<?php echo s("delete-events")?>"/>
  {{else}}
  <p id="no-meeting"><?php echo s("no-meeting")?></p>
  {{/if}}
</script>

<!-- User list -->
<script id="users-template" type="text/x-handlebars">
 {{#if users}}
    <p><small><?php echo s("users_instructions")?></small></p>
    <table id="users_table" class="tablesorter">
    <thead>
      <tr>
        <th>☑</th>
        <th> <?php echo s("Nom")?> </th>
        <th> <?php echo s("Numéro de téléphone")?> </th>
        <th> <?php echo s("Langues parlées")?> </th>
        <th> <?php echo s("events")?> </th>
      </tr>
    </thead>
    <tbody id="users">
        {{#each users}}
          <tr>
            <td><input type='checkbox' value='{{id}}'/></td>
            <td><span class='editable main-column' id='user_name_{{id}}'>{{name}}</span></td>
            <td><span class='editable' id='user_phone_{{id}}'>{{phone}}</span></td>
            <td>
              {{#each languages}}
                <span>{{languageName this}}</span>
              {{/each}}
            </td>
            <td>
              <ul>
              {{#each meetings}}
                <li>{{meetingTitle this}}</li>
              {{/each}}
              </ul>
            </td>
          </tr>
        {{/each}}
    </tbody>
  </table>
  <input id="delete_users_button" type="button" value="<?php echo s("Supprimer les contacts sélectionnés")?>"/>
 {{else}}
  <p id="no-user"><?php echo s("no-user")?></p>
 {{/if}}
</script>


<script type="text/javascript" src="js/view.js"></script>
<script>
  var localizedMessages = {
<?php foreach (I18N::$strings[$lang] as $code => $string) { print ('"' . $code . '": "' . $string . '", '); }; ?>
  }
  view.setup("<?php echo $lang?>", localizedMessages);
</script>


<!-- participant list for meeting -->
<script id="meetingParticipantsTemplate" type="text/x-handlebars">
        {{#each participants}}
          <tr>
            <td id="meeting_user_{{participantId}}" class="meeting_user_button">{{userName userId}}</td>
            <td>
              <select class='participant_status' id='meetingUserIdStatus_{{participantId}}'>
                <option value='yes' {{#if isComing}} selected {{/if}}><?php echo s('yes') ?></option>
                <option value='no' {{#if isNotComing}} selected {{/if}}><?php echo s('no') ?></option>
                <option value='maybe' {{#if isMaybeComing}} selected {{/if}}><?php echo s('maybe') ?></option>
                <option value='unknown' {{#if unknownComing}} selected {{/if}}><?php echo s('not-answered') ?></option>
            </td>
            <td>{{#if messageUrl}}<a target='_blank' href='{{messageUrl}}'>message</a>{{else}}-{{/if}}</td>
            <td>{{nbTimesCalled}}</td>
            <td>
              <input class='participant_call' id='meetingUserIdCall_{{participantId}}' type='button' value='<?php echo s("call") ?>'/>
              <input class='participant_remove' id='meetingUserIdRemove_{{participantId}}' type='button' value='<?php echo s("remove") ?>'>
            </td>
          </tr>
        {{/each}}
</script>

<!-- template: user list for new meeting form -->
<script id="newMeetingFormUsersTemplate" type="text/x-handlebars">
    {{#each users}}
      <input class='new_meeting_user' type='checkbox' value='{{id}}'/>{{name}}<br/>
    {{/each}}
</script>

<!-- template: creator list for new meeting form -->
<script id="newMeetingFormCreatorTemplate" type="text/x-handlebars">
  <select id="new_meeting_creator">
  <option value='0'><?php echo s("select") ?></option>
  {{#each admins}}
  <option value='{{id}}'>{{name}}</option>
  {{/each}}
  </select>
</script>


<!-- template: language list for new meetings form -->
<script id="newMeetingFormLanguagesTemplate" type="text/x-handlebars">
  <table id='new_meeting_languages_table'>
    {{#each languages}}
      <tr>
        <td><input class='languageSelect' type='checkbox' value='{{id}}'/></td>
        <td>{{languageName id}}</td>
        <td class='hidden notUploadedYet recorder' id='recorder_{{code}}'>
          <form id='upload_{{code}}' enctype='multipart/form-data'>
            <input name='uploadfile' type='file' />
            <input accept='audio/x-wav' id='audio_upload_{{code}}' type='button' value='<?php echo s("envoyer")?>' />
          </form>
        </td>
      </tr>
    {{/each}}
  </table>
</script>

<!-- template: language list for new user form -->
<script id="newUserFormLanguagesTemplate" type="text/x-handlebars">
    {{#each languages}}
       <span><input type='checkbox' value='{{id}}'/><span>{{languageName id}}</span>&nbsp;</span>
    {{/each}}
</script>

<!-- template: "add participant" dropdown in meeting  -->
<script id="meetingUserAddTemplate" type="text/x-handlebars">
  <select id='meeting_user_add_{{meetingId}}' class='meeting_user_add'>
    <option value='select'><?php echo s("add-participant") ?></option>
    {{#each users}}
    <option value='user_{{id}}'>{{name}}</option>
    {{/each}}
  </select>
</script>

<!-- template: editable text control  -->
<script id="editableTemplate" type="text/x-handlebars">
  <input id='editing' type='text' value='{{this}}'/>
</script>


<script type="text/javascript">
  $(document).ready(function () {
    $.datepicker.setDefaults($.datepicker.regional['<?php echo $lang?>']);
    $.timepicker.setDefaults($.timepicker.regional['<?php echo $lang?>']);
    $('#new_meeting_start_datetime').datetimepicker({ defaultDate: +1, showButtonPanel: true  });
    $('#new_meeting_end_datetime').datetimepicker({ defaultDate: +2, showButtonPanel: true  });
  });

</script>


<!-- ###### things that pop up ######################################################## -->
  <div id="selectParticipantsPopup" style="display:none" title="<?php echo s("Sélectionner les participants")?>">
    <div id="new_meeting_form_users_placeholder">
    </div>
    <input type="button" onclick="$('#selectParticipantsPopup').dialog('close')" value="<?php echo s('ok')?>">
  </div>

<img src='css/throbber_2.gif' style="position:absolute; left: -100000px" alt=""/>

<!-- ##### more scripts ##### -->

  <script type="text/javascript" src="js/i18n.js"></script>
  <script type="text/javascript" src="js/controller.js"></script>

  <script src="js/swfobject.js" type="text/javascript"></script>
  <script src="js/recorders.js" type="text/javascript"></script>

<!-- ##### View controls ##### -->

</body>
</html>
