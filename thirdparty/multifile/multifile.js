/**
 * Convert a single file-input element into a 'multiple' input list
 * 
 * Modified 2006-11-06 by Silverstripe Ltd.
 *
 * Usage:
 *
 *   1. Create a file input element (no name)
 *      eg. <input type="file" id="first_file_element">
 *
 *   2. Create a DIV for the output to be written to
 *      eg. <div id="files_list"></div>
 *
 *   3. Instantiate a MultiSelector object, passing in the DIV and an (optional) maximum number of files
 *      eg. var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 3 );
 *
 *   4. Add the first element
 *      eg. multi_selector.addElement( document.getElementById( 'first_file_element' ) );
 *
 *   5. That's it.
 *
 *   You might (will) want to play around with the addListRow() method to make the output prettier.
 *
 *   You might also want to change the line 
 *       element.name = 'file_' + this.count;
 *   ...to a naming convention that makes more sense to you.
 * 
 * Licence:
 *   Use this however/wherever you like, just don't blame me if it breaks anything.
 *
 * Credit:
 *   If you're nice, you'll leave this bit:
 *  
 *   Class by Stickman -- http://www.the-stickman.com
 *      with thanks to:
 *      [for Safari fixes]
 *         Luis Torrefranca -- http://www.law.pitt.edu
 *         and
 *         Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com
 *      [for duplicate name bug]
 *         'neal'
 */
 
// Modified by: Silverstripe Ltd. (changed naming of file-input-elements) 

function ObservableObject() {
    this.functions = [];
}
ObservableObject.prototype = {
    subscribe : function(evt, fn) {
        this.functions.push([evt, fn]);
    },
    unsubscribe : function(evt, fn) {
        this.functions = this.fns.filter(function(el) {if (el !== [evt, fn]) return el;});
    },
		fire : function(evt, data, scope) {
			scope = scope || window
			jQuery(this.functions).each(function(el) {
				if (el[0] == evt) el[1].call(scope, data);
			});
		}
};

var MultiSelectorObserver = new ObservableObject();

function MultiSelector( list_target, max, upload_button ){
	
	this.upload_button = upload_button;
	this.upload_button.setAttribute("disabled", "disabled"); 

	// Where to write the list
	this.list_target = list_target;
	// How many elements?
	this.count = -1; 
	// How many elements?
	this.id = 0;
	// Is there a maximum?
	if( max ){
		this.max = max;
	} else {
		this.max = -1;
	};
	
	/**
	 * Add a new file input element
	 */
	this.addElement = function( element ){

		// Make sure it's a file input element
		if( element.tagName == 'INPUT' && element.type == 'file' ){

			// Element name -- what number am I?
			// Modified by: Silverstripe Ltd. (changed naming of file-input-elements)
			element.name = 'Files[' + this.id++ + ']'

			// If we've reached maximum number, disable input element
			if( this.max != -1 && this.count >= this.max ){
				element.disabled = true;
			};

			// File element counter
			this.count++;

			// Add reference to this object
			element.__scope = this;
			element.multi_selector = this;

			// What to do when a file is selected
			element.onchange = function(){

				// New file input
				var new_element = document.createElement( 'input' );
				new_element.type = 'file';

				// Add new element
				this.parentNode.insertBefore( new_element, this );

				// Apply 'update' to element
				this.multi_selector.addElement( new_element );

				// Update list
				this.multi_selector.addListRow( this );

				// Hide this: we can't use display:none because Safari doesn't like it
				this.style.position = 'absolute';
				this.style.left = '-1000px';
				
				element.__scope.toggleUploadButton();
			};
			
			// Most recent element
			this.current_element = element;
			
		} else {
			// This can only be applied to file input elements!
			alert( 'Error: not a file input element' );
		};

	};
	
	this.toggleUploadButton = function() {
		if(this.count > 0) {
			this.upload_button.removeAttribute("disabled"); 
		} else {
			this.upload_button.setAttribute("disabled", "disabled"); 
		}
	}

	/**
	 * Add a new row to the list of files
	 */
	this.addListRow = function( element ){
		var fileId = this.id -1; 

		// Modified 2006-11-06 by Silverstripe Ltd.
		var filenameMatches = element.value.match(/([^\/\\]+)$/);
		var filename = filenameMatches[1];
		
		// Row div
		var new_row = document.createElement('div');
		new_row.className = 'fileBox'; 

		// Delete button
		var new_row_button = document.createElement('input');
		new_row_button.type = 'button';
		new_row_button.value = 'Delete';
		new_row_button.setAttribute('class', 'delete');

		// References
		new_row.element = element;
		element.__scope = this,		

		// Delete function
		new_row_button.onclick= function(){

			// Remove element from form
			this.parentNode.element.parentNode.removeChild( this.parentNode.element );

			// Remove this row from the list
			this.parentNode.parentNode.removeChild( this.parentNode );

			// Decrement counter
			this.parentNode.element.multi_selector.count--;

			// Re-enable input element (if it's disabled)
			this.parentNode.element.multi_selector.current_element.disabled = false;
			
			element.__scope.toggleUploadButton();

			// Appease Safari
			//    without it Safari wants to reload the browser window
			//    which nixes your already queued uploads
			return false;
		};

		// Set row value
		// Modified 2006-11-06 by Silverstripe Ltd.
		new_row.innerHTML = filename;
		
		MultiSelectorObserver.fire('onBeforeCreateRow', [fileId, new_row], this); 

		// Add button
		new_row.appendChild( new_row_button );

		// Add it to the list
		this.list_target.appendChild( new_row );
		
		MultiSelectorObserver.fire('onAfterCreateRow', [fileId, new_row], this); 
		
		// Modified 2006-11-06 by Silverstripe Ltd.
		if(typeof(window.ontabschanged) != 'undefined') window.ontabschanged();
		
	};

};

MultiSelectorObserver.subscribe('onBeforeCreateRow', function(data) {
	if (jQuery('#metadataFormTemplate').length) {
		var parameters = jQuery('#metadataFormTemplate').clone(true);
		parameters.get(0).id = '';
		parameters.find(":input[name!='']").each(function(i) { this.name = this.name.replace(/__X__/g, data[0]); });
		parameters.find(":input[id!='']").each(function(i) { this.id = this.id.replace(/__X__/g, data[0]); });
		data[1].innerHTML = data[1].innerHTML + '<div id="MetadataFor-'+data[0]+'">'+parameters.html()+'</div>';
	}
});