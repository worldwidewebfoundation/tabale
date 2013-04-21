/*jslint devel: true, browser: true, maxerr: 50, indent: 2 */

var $;

var I18N = {};
I18N.languageNames = {"en": {"en": "English", "fr": "French", "bam": "Bambara", "bmq": "Bomu", "dts": "Dogon"},
                      "fr": {"en": "anglais", "fr": "français", "bam": "bambara", "bmq": "bomou", "dts": "dogon"}};


I18N.recorderMessages = {
  "en": {
    "readyToRecord": "Ready to record",
    "noMicrophone": "No microphone found",
    "talkNow": "Talk now",
    "saveRecording": "Save your recording",
    "recording": "Recording",
    "success": "done"
  },
  "fr": {
    "readyToRecord": "Prêt à enregistrer",
    "noMicrophone": "Pas de micro",
    "talkNow": "Parlez maintenant",
    "saveRecording": "Sauvegarder l'annonce",
    "recording": "Sauvegarde",
    "success": "Sauvegarde terminée"
  }
};


$.datepicker.regional.fr = {
  closeText: 'OK',
  prevText: '<Précédent',
  nextText: 'Suivant>',
  currentText: 'Maintenant',
  monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
  monthNamesShort: ['Jan', 'Fév', 'Маr', 'Аvr', 'Маi', 'Jun', 'Jui', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
  dayNames: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
  dayNamesShort: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
  dayNamesMin: ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
  weekHeader: 'Semaine',
  dateFormat: 'dd/mm/yy',
  firstDay: 1,
  isRTL: false,
  showMonthAfterYear: false,
  yearSuffix: ''
};
$.timepicker.regional.fr = {
  timeOnlyTitle: 'Choix de l\'heure',
  timeText: 'Heure',
  hourText: 'Heure',
  minuteText: 'Minute',
  secondText: 'Seconde',
  millisecText: 'Milliseconde',
  timezoneText: 'Fuseau Horaire',
  currentText: 'Maintenant',
  closeText: 'OK',
  timeFormat: 'hh:mm',
  amNames: ['AM', 'A'],
  pmNames: ['PM', 'P'],
  isRTL: false
};

$.datepicker.regional.en = {
  closeText: 'OK',
  prevText: '<Previous',
  nextText: 'Next>',
  currentText: 'Now',
  monthNames: ['January', 'February', 'March', 'April', 'May', 'June',  'July', 'August', 'September', 'October', 'November', 'December'],
  monthNamesShort: ['Jan', 'Feb', 'Маr', 'Аpr', 'Маy', 'Jun',  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
  dayNamesShort: ['Sun', 'Mon', 'Tue', 'Web', 'Thu', 'Fri', 'Sat'],
  dayNamesMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
  weekHeader: 'Week',
  dateFormat: 'dd/mm/yy',
  firstDay: 1,
  isRTL: false,
  showMonthAfterYear: false,
  yearSuffix: ''
};
$.timepicker.regional.en = {
  timeOnlyTitle: 'Choose a time',
  timeText: 'Time',
  hourText: 'Hour',
  minuteText: 'Minute',
  secondText: 'Second',
  millisecText: 'Millisecond',
  timezoneText: 'Time Zone',
  currentText: 'Now',
  closeText: 'OK',
  timeFormat: 'hh:mm',
  amNames: ['AM', 'A'],
  pmNames: ['PM', 'P'],
  isRTL: false
};
