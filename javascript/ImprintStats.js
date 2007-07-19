
/********************************************************
 * Copyright Nexsys Development Ltd 2002 - 2004         *
 * Version 1.7                                          *
 * http://www.nexsysdev.com                             *
 ********************************************************/
 
var NI_SE = "", NI_SC = "", NI_ST = "", NI_SV = "", NI_IW = 0;

function ni_TrackHit(server, siteCode, description, section, service, trigger, amount, adCampaign, title, url, layer, basketAdd, basketRemove, parameters)
{ 
   NI_SE = server; NI_SC = siteCode; NI_ST = section; NI_SV = service;
   
   function CB()
   {
      var cb="", key = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";            
      for (i=0;i<5;i++) cb += key.charAt(Math.floor(Math.random()*52));      
      return cb;
   }

   function A(B,C)
   {
      if (typeof(C) != "undefined" && C != "") return "&"+B+"="+escape(C);
      else return "";
   }
   
   if (typeof(NI_PAGE) != "undefined")
   {
     if (url.indexOf("?") > 0) url += "&ni_page=" + NI_PAGE;
     else url += "?ni_page=" + NI_PAGE;
   }
	
   var p = "http"+(document.URL.indexOf('https:')==0?'s':''); 	
   var t = new Date();	
   var u = p+"://"+server+"/Hit.aspx?tv=1&sc="+siteCode;
   u+=A("lo",description);
   u+=A("du",url);
   u+=A("st",section);
   u+=A("sv",service);
   u+=A("ac",adCampaign);
   u+=A("tr",trigger);
   u+=A("ta",amount);
   u+=A("ti",title);
   u+=A("tz",t.getTimezoneOffset());
   u+=A("ch",t.getHours());
   u+=A("cb",CB());	
   u+=A("ru",window.document.referrer);
   u+=A("js","1");
   u+=A("ul",navigator.appName=="Netscape" ? navigator.language : navigator.userLanguage);
   u+=A("ba", basketAdd);
   u+=A("br", basketRemove);
   u+=A("pm", parameters);
	
   if (typeof(screen)=="object")
   {
      u+=A("sr",screen.width+"x"+screen.height);	
   }
	
	if (layer == 1)
	{
	  if (NI_IW == 0) { document.write('<div style="position:absolute;width:1px;height:1px;overflow:hidden"><IMG name="ni_tag" id="ni_tag" border="0" width="1" height="1" src="'+u+'"></div>'); NI_IW = 1; }
	  else { u+=A("ir","1"); document.images.ni_tag.src = u; }
 	}
 	else
 	{
 	  document.write('<IMG name="ni_tag" id="ni_tag" border="0" width="1" height="1" src="'+u+'">'); NI_IW = 2;
 	}
}

/* The following function may be used any number of times in a page to load a file and track a hit 
 * against that file.  This is useful when the file being loaded is not html, 
 * or is not under your control, so can't have an imprint tracking code inserted into it.
 *
 * E.g. <a href="javascript:ni_LoadUrl('Catalogue.pdf', 'Catalogue')">Download catalogue</a>
 *
 * If you consider clicking on the link to be the completion of a transaction, use the 'Sale' trigger
 * E.g. <a href="javascript:ni_LoadUrl('Catalogue.pdf', 'Catalogue', 'Sale')">Download catalogue</a>
 */
function ni_LoadUrl(url, title, trigger)
{  
  ni_TrackHit(NI_SE, NI_SC, "", NI_ST, NI_SV, trigger, "", "", title, url, NI_IW, "", "", "");
  document.location.href = url;
}		
		