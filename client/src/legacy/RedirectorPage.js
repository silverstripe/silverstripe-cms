import $ from 'jquery';

$.entwine('ss', function ($) {
  $('#Form_EditForm_RedirectionType input').entwine({
    onmatch: function () {
      var self = $(this);
      if (self.attr('checked')) this.toggle();
      this._super();
    },
    onunmatch: function () {
      this._super();
    },
    onclick: function () {
      this.toggle();
    },
    toggle: function () {
      if ($(this).attr('value') == 'Internal') {
        $('#Form_EditForm_ExternalURL_Holder').hide();
        $('#Form_EditForm_LinkToID_Holder').show();
        $('#Form_EditForm_LinkToFile_Holder').hide();
      } else if ($(this).attr('value') == 'External') {
        $('#Form_EditForm_ExternalURL_Holder').show();
        $('#Form_EditForm_LinkToID_Holder').hide();
        $('#Form_EditForm_LinkToFile_Holder').hide();
      } else {
        $('#Form_EditForm_LinkToFile_Holder').show();
        $('#Form_EditForm_ExternalURL_Holder').hide();
        $('#Form_EditForm_LinkToID_Holder').hide();
      }
    },
  });
});
