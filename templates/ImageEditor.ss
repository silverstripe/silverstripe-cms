<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" debug=true>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <% base_tag %>
        <title>Untitled Document</title>
        <meta http-equiv="imagetoolbar" content="no">
    </head>
    <body id="body" onload="imageEditor = new ImageEditor.initialize('$fileToEdit');">
        <div id="Loading" style="background: #FFF url(cms/images/loading.gif) 50% 50% no-repeat; position: absolute;z-index: 100000;height: 100%;width: 100%;margin: 0;padding: 0;z-index: 100000;position: absolute;">Loading...</div>
        <div id="Main">
            <script type="text/javascript">
            </script>
            <div id="MenuBar">
                <div id="TopLeft"></div>
                <div id="TopRight"></div>
                <div id="Filename">
                    <p>$fileToEditOnlyName</p>
                </div>
                <div id="Actions">
                    <a id="SaveButton" href="#">
                        <div id="SaveIcon">
                        </div>
                        <p id="SaveText" class="menuText">
                            save&nbsp;image
                        </p>
                    </a>
                    <a id="ExitButton" href="#">
                        <div id="ExitIcon">
                        </div>
                        <p id="ExitText" class="menuText">
                            exit
                        </p>
                    </a>
                    <a id="UndoButton" href="#">
                        <div id="UndoIcon">
                        </div>
                        <p id="UndoText" class="menuText">
                            undo
                        </p>
                    </a>
                    <a id="RedoButton" href="#">
                        <div id="RedoIcon">
                        </div>
                        <p id="RedoText" class="menuText">
                            redo
                        </p>
                    </a>
                    <p id="ActionsDescription" class="menuText">
                        actions
                    </p>    
                </div>
                <div id="Functions">
                    <a id="RotateButton" href="#">
                        <div id="RotateIcon">
                        </div>
                        <p id="RotateText" class="menuText">
                            rotate
                        </p>
                    </a>
                    <a id="CropButton" href="#">
                        <div id="CropIcon"></div>                    
                        <p id="CropText" class="menuText">
                            crop
                        </p>
                    </a>
                    <div id="ImageSize">
                        <p id="ImageWidthLabel" class="menuText">width</p>
                        <p id="ImageHeightLabel" class="menuText">height</p>
                        <p id="ImageWidth" class="menuText"></p>
                        <p id="ImageHeight" class="menuText"></p>
                    </div>
                    <p id="FunctionsDescription" class="menuText">
                        edit&nbsp;functions
                    </p>    
                </div>
                <div id="CurrentAction">
                    <a id="CancelButton" href="#">
                        <div id="CancelIcon">
                        </div>
                        <p id="CancelText" class="menuText">
                            cancel
                        </p>
                    </a>
                    <a id="ApplyButton" href="#">
                        <div id="ApplyIcon">
                        </div>
                        <p id="ApplyText" class="menuText">
                            apply
                        </p>
                    </a>
                    <p id="CurrentActionDescription" class="menuText">
                        current&nbsp;action
                    </p>  
                </div>                
            </div>              
            <div id="TopRuler"></div>
            <div id="LeftRuler"></div>
            <div id="imageEditorContainer">             
                <div id="imageContainer">
                    <div id="leftGreyBox" class="greyBox"></div>
                    <div id="rightGreyBox" class="greyBox"></div>
                    <div id="upperGreyBox" class="greyBox"></div>
                    <div id="lowerGreyBox" class="greyBox"></div>   
                    <img id="image" src="#" alt=""/>
                    <div id="cropBox"></div>
                    <div id="loadingIndicatorContainer">
                        <img id="loadingIndicator" alt="" src="cms/images/ImageEditor/indicator.gif">
                    </div>  
                </div>
            </div>
        </div>
        <div id="fakeImgContainer">
            <img id="fakeImg" src="#" alt=""/>
        </div>
        <div id="loadingIndicatorContainer2">
            <img id="loadingIndicator2" alt="" src="cms/images/ImageEditor/indicator.gif">
        </div>
        <p id="statusMessage" style="visibility:hidden"></p>
        <script type="text/javascript">
            if(window.onload) old_onload = window.onload;
            window.onload = function() {
                Element.hide($('Loading'));
                old_onload();
            };
        </script>
        
    </body> 
</html>