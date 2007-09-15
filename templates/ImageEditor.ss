<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<% base_tag %>
		<title>Untitled Document</title>
		<link rel="stylesheet" type="text/css" media="all" href="/css/main.css" />								
	</head>
	<body id="body">
		<div id="mainContainer">
			<div id="menuBarContainer">
				<div id="photoInfoContainer" class="floatRight">
					<p>Size</p>
					<p class="inline">Width:</p><p id="imageWidth" class="inline">0px</p>															
					<p class="inline">Height:</p><p id="imageHeight" class="inline">0px</p>						
				</div>
				<div id="historyContainer" class="floatRight"> 
					<p><a id="undoButton" href="#">Undo</a></p>
					<p><a id="redoButton" href="#">Redo</a></p>
				</div>				
				<div id="effectsContainer" class="floatRight">
					<p><a id="rotateButton" href="#">Rotate</a></p>					
				</div>
				<div id="cropContainer" class="floatRight"> 
					<p><a id="cropStart" href="#">Crop start</a></p>					
					<p><a id="cropOk" href="#" class="hidden">Ok</a></p>					
					<p><a id="cropCancel" href="#" class="hidden">Cancel</a></p>					
				</div>
				<div id="operationContainer" class="floatRight">
					<p><a id="saveButton" href="#">Save</a></p>					
					<p><a id="closeButton" href="#">Cancel</a></p>
				</div>
			</div>				
			<div id="imageEditorContainer">				
				<div id="imageContainer">
					<div id="leftGreyBox" class="greyBox"></div>
					<div id="rightGreyBox" class="greyBox"></div>
					<div id="upperGreyBox" class="greyBox"></div>
					<div id="lowerGreyBox" class="greyBox"></div>										
					<img id="image" src="#" alt="We have encountered an error"/>						
					<div id="cropBox"></div>
				</div>	
				<div id="loadingIndicatorContainer">
					<img id="loadingIndicator" alt="indicator" src="/cms/images/ImageEditor/indicator.gif">
				</div>												
			</div>			
		</div>
		<div id="fakeImgContainer">
			<img id="fakeImg" src="#" alt="fakeImg"/>
		</div>
		<script type="text/javascript">imageEditor = new ImageEditor.initialize("$fileToEdit")</script>		
	</body>	
</html>