/**
 * Ajax to support the comment posting system
 */

PageCommentInterface = Class.create();

PageCommentInterface.prototype = {
	initialize: function() {
		Behaviour.register({
			'#PageCommentInterface_Form_PostCommentForm_action_postcomment' : {
				onclick : this.postComment
			},

			'#PageComments a.deletelink' : {
				onclick : this.deleteComment
			},
			'#PageComments a.spamlink' : {
				onclick : this.reportSpam
			},
			'#PageComments a.hamlink' : {
				onclick : this.reportHam	
			},
			'#PageComments a.approvelink' : {
				onclick : this.approveComment	
			}
		});
	},
	
	loadSpamQuestion: function(response) {
		var spamQuestionDiv = $('Math');
		var mathLabel = spamQuestionDiv.getElementsByTagName('label')[0];
		mathLabel.innerHTML = response.responseText;
		var mathQuestion = spamQuestionDiv.getElementsByTagName('input')[0];
		mathQuestion.value = '';
	},
	
	postComment: function() {
		var form = $("PageCommentInterface_Form_PostCommentForm");
		var message = $("PageCommentInterface_Form_PostCommentForm_error");
		
		if(form.elements.Name.value && form.elements.Comment.value) {
			if(noComments = $('NoComments')) {
				Element.remove(noComments);
				var pageComments = document.createElement('ul');
				pageComments.id = 'PageComments';
				$('CommentHolder').appendChild(pageComments);
			}
			
			message.style.display = 'none';
		
			// Create a new <li> for the post
			var pageComments = $('PageComments').getElementsByTagName('li');
			var __newComment = document.createElement('li');

	
						// Add it to the list with a 'loading' message
			$('PageComments').insertBefore(__newComment, pageComments[0]);
			__newComment.innerHTML = '<p><img src="cms/images/network-save.gif" /> Loading...</p>';

			
			// Submit the form via ajax
			Ajax.SubmitForm(form, "action_postcomment", {
				onSuccess : function(response) {
					
					// Create an Ajax request to regenerate the spam protection question
					//need to check if there is actually a spam question to change first
					if(form.elements.Math){
						new Ajax.Request(document.getElementsByTagName('base')[0].href+'PageCommentInterface_Controller/newspamquestion', {
							onSuccess: loadSpamQuestion
						});
					}
					
					if(response.responseText != "spamprotectionfailed"){
								__newComment.className ="even";
						// Load the response into the new <li>
						__newComment.innerHTML = response.responseText;
						Behaviour.apply(__newComment);
						
						// Flash it using Scriptaculous
						new Effect.Highlight(__newComment, { endcolor: '#e9e9e9' } );
						if(response.responseText.match('<b>Spam detected!!</b>')) {
							__newComment.className = 'spam';
						}
					
					} else {
						__newComment.innerHTML = "";
						Behaviour.apply(__newComment);
						message.style.display = '';
						message.innerHTML = "You got the spam question wrong.";
						
					}
					
				},
				onFailure : function(response) {
					alert(response.responseText);
				}
			});
		} else {
			message.style.display = '';
			message.innerHTML = "Please enter your name and a comment to be posted to the site.";	
		}
		
		return false;
	},
	
	/**
	 * Ajax handler of moderation removal
	 */
	deleteComment: function() {
		var __comment = this.parentNode.parentNode.parentNode;
		
		__comment.getElementsByTagName('span')[0].innerHTML = "Removing...";
		
		new Ajax.Request(this.href + '?ajax=1', {
			onSuccess : function(response) {
				// Clear our wee status message
				__comment.getElementsByTagName('span')[0].innerHTML = "Removing...";

				// Remove it using Scriptaculous
				new Effect.Highlight(__comment, { 
					startcolor: '#cc9999' , endcolor: '#e9e9e9', duration: 0.5,
					afterFinish : function () { 
						var commentList = __comment.parentNode;
						commentList.removeChild(__comment);
						if(!commentList.firstChild) {
							$('CommentHolder').innerHTML = "<p id=\"NoComments\">No one has commented on this page yet.</p>";
						}
					}
				} );
			},
			
			onFailure : function(response) {
				alert(response.responseText);
			}
		});
		
		return false;
	},
	
	/**
	 * Ajax handler of spam reporting
	 */
	 reportSpam: function() {
	 	var __comment = this.parentNode.parentNode.parentNode.parentNode;
	 	
	 	__comment.getElementsByTagName('span')[0].innerHTML = "Reporting spam...";
	 	
	 	
	 	new Ajax.Request(this.href + '?ajax=1', {
	 		onSuccess : function(response) {
	 			if(response.responseText != '') {
	 				// Load the response into the <li>
	 				__comment.innerHTML = response.responseText;
					Behaviour.apply(__comment);
				
					// Flash it using Scriptaculous
					new Effect.Highlight(__comment, { endcolor: '#cc9999' } );
					
					__comment.className = 'spam';
				} else {
					new Effect.Highlight(__comment, { 
						startcolor: '#cc9999' , endcolor: '#e9e9e9', duration: 0.5,
						afterFinish : function() {
	 						var commentList = __comment.parentNode;
							commentList.removeChild(__comment);
							if(!commentList.firstChild) {
								$('CommentHolder').innerHTML = "<p id=\"NoComments\">No one has commented on this page yet.</p>";
							}
						}
					} );
				}
			},

			onFailure : function(response) {
				alert(response.responseText);
			}
		});
		
		return false;
	},
	
	/**
	 * Ajax handler of ham reporting
	 */
	 reportHam: function() {
	 	var __comment = this.parentNode.parentNode.parentNode.parentNode;
	 	
	 	__comment.getElementsByTagName('span')[0].innerHTML = "Reporting as not spam...";
	 	
	 	new Ajax.Request(this.href + '?ajax=1', {
	 		onSuccess : function(response) {
	 			// Load the response into the <li>
	 			__comment.innerHTML = response.responseText;
				Behaviour.apply(__comment);
				
				// Flash it using Scriptaculous
				new Effect.Highlight(__comment, { endcolor: '#e9e9e9' } );
				__comment.className = 'notspam';
			},
			
			onFailure : function(response) {
				alert(response.responseText);
			}
		});
		
		return false;
	},
	
	/**
	 * Ajax handler of ham reporting
	 */
	 approveComment: function() {
	 	var __comment = this.parentNode.parentNode.parentNode.parentNode;
	 	
	 	__comment.getElementsByTagName('span')[0].innerHTML = "Marking comment as approved...";
	 	
	 	new Ajax.Request(this.href + '?ajax=1', {
	 		onSuccess : function(response) {
	 			// Load the response into the <li>
	 			__comment.innerHTML = response.responseText;
				Behaviour.apply(__comment);
				
				// Flash it using Scriptaculous
				new Effect.Highlight(__comment, { endcolor: '#e9e9e9' } );
				__comment.className = 'notspam';
			},
			
			onFailure : function(response) {
				alert(response.responseText);
			}
		});
		
		return false;
	}
}
 
PageCommentInterface.applyTo("#PageComments_holder");
function loadSpamQuestion(response) {
	var spamQuestionDiv = $('Math');
	var mathLabel = spamQuestionDiv.getElementsByTagName('label')[0];
	mathLabel.innerHTML = response.responseText;
	var mathQuestion = spamQuestionDiv.getElementsByTagName('input')[0];
	mathQuestion.value = '';
}
