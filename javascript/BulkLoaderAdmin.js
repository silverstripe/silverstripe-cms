/**
 * Configuration for the left hand tree
 */
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.loadPage_url = 'admin/bulkload/getitem';
SiteTreeHandlers.showRecord_url = 'admin/bulkload/show/';;

Behaviour.register({
	'#Form_EditForm' : {
		getPageFromServer: function (className) {
			$('BulkLoaderIframe').src = 'admin/bulkload/iframe/' + className;
		}	
	}
	
});