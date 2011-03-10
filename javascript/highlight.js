/*
highlightImages_over * common/js/highlight.js
 * Behaviour-abstracted highligher modul
 *
 * Usage: 
 *  - include <script src="/common/js/highlight.js"></script> in the header of your file
 *  - set the class of the container to "highlight".  The class will have ' over' appended to it on mouseover
 *  - set the overSrc attribute on any images inside that, if you want them to be changed
 */

/*
 * Initialise all items that have a highlight or highlightImages class with the appropriate event handlers
 */
 
 
function onload_init_highlight() {
	var allElements = getAllElements();
	var tester,j,overSrc;
	
	for(var i = 0;i<allElements.length;i++) {
		if(allElements[i].className) {
			tester = ' ' + allElements[i].className + ' ';
			if(tester.indexOf(' highlight ') != -1) {
				
				addEventHandler(allElements[i], 'onmouseover', highlightImages_over);
				addEventHandler(allElements[i], 'onmouseout', highlightImages_out);
				addEventHandler(allElements[i], 'onfocus', highlightImages_over);
				addEventHandler(allElements[i], 'onblur', highlightImages_out);
				removeClass(allElements[i], 'highlight');

				var allImages = allElements[i].getElementsByTagName('img');
				for(j=0;j<allImages.length;j++) {
					if(overSrc = allImages[j].getAttribute('overSrc')) {
						allImages[j].normSrc = allImages[j].src;
						allImages[j].overSrc = overSrc;
						preload(allImages[j].overSrc);
					
					} else if(_IMAGE_HIGHLIGHT_REWRITING.length > 0) {
						overSrc = allImages[j].src;
						for(k=0;k<_IMAGE_HIGHLIGHT_REWRITING.length;k++)
							overSrc = overSrc.replace(_IMAGE_HIGHLIGHT_REWRITING[k].norm,_IMAGE_HIGHLIGHT_REWRITING[k].over);
						if(overSrc != allImages[j].src) {
							allImages[j].normSrc = allImages[j].src;
							allImages[j].overSrc = overSrc;
						}
					}
				}
			}
		}
	}
}

/*
 * Highlight handlers
 */
function highlightImages_over() {
	var el = this;
//	if(el.id) document.getElementById('SearchField').document.getElementById('SearchField').innerHTML += "Over: " + el.id + "<BR>";

	// Reset any timer that may exist
	if(el.timer != null) {
		clearTimeout(el.timer);
		try {
			delete el.timer; 
		} catch(er) {}
	}
	
	// Class
	if(el) {
		addClass(el, 'over');
		removeClass(el, 'stale');
	
		if(el.parentNode) {
			el.parentNode.highlightedChild = el;
			addClass(el.parentNode,'childOver');
		}
	}
	
	// Images
	var allImages = el.getElementsByTagName('img');
	for(var j=0;j<allImages.length;j++) {
		
		if(allImages[j].overSrc){ allImages[j].src = allImages[j].overSrc; }
	}
	
	if(el.old_onmouseover) el.old_onmouseover();
}

var _HIGHLIGHTED_OBJECTS = Array();

var _DEFAULT_UNHIGHLIGHT_DELAY = Array();
function setUnhighlightDelay(val, classOrId) {
	if(classOrId == null) classOrId = 'default';
	_DEFAULT_UNHIGHLIGHT_DELAY[classOrId] = val;
}


function highlightImages_out(idx) {
	var unhighlightDelay, nextIdx;

	// idx can be passed by a call from setTimeout().  Hurrah for delayed unhighlighting
	if(parseInt(idx) > 0) {
		var el = _HIGHLIGHTED_OBJECTS[idx];
		delete _HIGHLIGHTED_OBJECTS[idx];
		unhighlightDelay = null;
		
	} else {
		var el = this;
		if(el && el.getAttribute) {
			unhighlightDelay = el.getAttribute("unhighlightDelay");
			if(unhighlightDelay == null) {
				if(_DEFAULT_UNHIGHLIGHT_DELAY[el.parentNode.className]) unhighlightDelay = _DEFAULT_UNHIGHLIGHT_DELAY[el.parentNode.className];
				else if(_DEFAULT_UNHIGHLIGHT_DELAY[el.parentNode.id]) unhighlightDelay = _DEFAULT_UNHIGHLIGHT_DELAY[el.parentNode.id];
				else if(_DEFAULT_UNHIGHLIGHT_DELAY['default']) unhighlightDelay = _DEFAULT_UNHIGHLIGHT_DELAY['default'];
			}
		}
	}
	
	if(unhighlightDelay) {
		nextIdx = _HIGHLIGHTED_OBJECTS.length;
		if(nextIdx == 0) nextIdx = 1;
		_HIGHLIGHTED_OBJECTS[nextIdx] = el;
		el.timer = setTimeout("highlightImages_out(" + nextIdx + ")", unhighlightDelay);		
		
	} else {
		// Class
		if(el) {
			removeClass(el, 'over');
			removeClass(el, 'stale');
	
	
			if(el.parentNode) {
				if(!el.parentNode.highlightedChild || el == el.parentNode.highlightedChild) {
					removeClass(el.parentNode,'childOver');
					el.parentNode.highlightedChild = null;
				}
			}
		
			// Images
			var allImages = document.getElementsByTagName('img');
			for(var j=0;j<allImages.length;j++) {
				if(allImages[j].normSrc) allImages[j].src = allImages[j].normSrc;
			}
			
			// Stale
			if(el.getAttribute) {
				var staleDelay = parseInt(el.getAttribute("staleDelay"));
				if(staleDelay > 0) {
					nextIdx = _HIGHLIGHTED_OBJECTS.length;
					_HIGHLIGHTED_OBJECTS[nextIdx] = el;
					el.timer = setTimeout("highlightImages_makeStale(" + nextIdx + ")", staleDelay);
				}
			}

			if(el.old_onmouseout) el.old_onmouseout();

		}
	}

}

// Add the stale class to the given item
function highlightImages_makeStale(idx) {
	var el = _HIGHLIGHTED_OBJECTS[idx];
	delete _HIGHLIGHTED_OBJECTS[idx];
	addClass(el, 'stale');
}

/*
 * Preload the given image
 */
 
var _PRELOADED_IMAGES = Array();
function preload(filename) {
	var i = new Image;
	i.src = filename;
	_PRELOADED_IMAGES[_PRELOADED_IMAGES.length] = i;
}

//go to the specified page.
//Works for nested elements. Always calls the most nested.
//Includes Bubble hack for mozilla

var pagelocation;
function go(url){
	if(pagelocation == null){
		window.location=url;
		pagelocation = url;
     }
}



/*
 * This function will show or hide a div when you 
 * pass it a element ID
 * Created by sgow added @ 16-09-2004
 */

/* variables needed */
var showDiv;
var oldDiv = 1;

function toggleDiv( el ){
	if(oldDiv == 1){
	//the first time you call this function
		if(showDiv = document.getElementById(el)) {
			showDiv.style.display = "block";
			oldDiv = showDiv;
		}
	}
	else{
		showDiv = document.getElementById(el);
		if( showDiv && showDiv == oldDiv ){
			if(oldDiv.style.display == "none"){	showDiv.style.display = "block";}
			else { showDiv.style.display = "none";}
			oldDiv = showDiv;
			// if its the same div as before, hide the same div, unless its the first time you use the function
		}else{
			oldDiv.style.display = "none";
			showDiv.style.display = "block";
			oldDiv = showDiv;
			// if its a different div, hide the first one, and show the second one
		}
	}
}

var _IMAGE_HIGHLIGHT_REWRITING = Array();
function setImageHighlightRewriting(normVal, overVal) {
	_IMAGE_HIGHLIGHT_REWRITING[_IMAGE_HIGHLIGHT_REWRITING.length] = {
		'norm' : normVal, 'over' : overVal
	}
}


appendLoader(onload_init_highlight);
