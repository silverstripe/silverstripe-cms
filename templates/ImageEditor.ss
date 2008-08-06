<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" debug=true>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <% base_tag %>
        <title><% _t('UNTITLED','Untitled Document') %></title>
        <meta http-equiv="imagetoolbar" content="no">
    </head>
    <body id="body" class="$CSSClasses" onload="ImageEditor.imageEditor = new ImageEditor.Main.initialize('$fileToEdit');">
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
                    <a id="SaveButton" href="#a">
                        <div id="SaveIcon">
                        </div>
                        <p id="SaveText" class="menuText">
                            <% _t('SAVE','save&nbsp;image') %>
                        </p>
                    </a>
                    <a id="ExitButton" href="#a">
                        <div id="ExitIcon">
                        </div>
                        <p id="ExitText" class="menuText">
                            <% _t('EXIT', 'exit') %>
                        </p>
                    </a>
                    <a id="UndoButton" href="#a">
                        <div id="UndoIcon">
                        </div>
                        <p id="UndoText" class="menuText">
                            <% _t('UNDO','undo') %>
                        </p>
                    </a>
                    <a id="RedoButton" href="#a">
                        <div id="RedoIcon">
                        </div>
                        <p id="RedoText" class="menuText">
                            <% _t('REDO','redo') %>
                        </p>
                    </a>
                    <p id="ActionsDescription" class="menuText">
                        <% _t('ACTIONS', 'actions') %>
                    </p>    
                </div>
                <div id="Functions">
                    <a id="RotateButton" href="#a">
                        <div id="RotateIcon">
                        </div>
                        <p id="RotateText" class="menuText">
                            <% _t('ROT','rotate') %>
                        </p>
                    </a>
                    <a id="CropButton" href="#a">
                        <div id="CropIcon"></div>                    
                        <p id="CropText" class="menuText">
                            <% _t('CROP','crop') %>
                        </p>
                    </a>
                    <div id="ImageSize">
                        <p id="ImageWidthLabel" class="menuText"><% _t('WIDTH','width') %></p>
                        <p id="ImageHeightLabel" class="menuText"><% _t('HEIGHT','height') %></p>
                        <p id="ImageWidth" class="menuText"></p>
                        <p id="ImageHeight" class="menuText"></p>
                    </div>
                    <p id="FunctionsDescription" class="menuText">
                        <% _t('EDITFUNCTIONS', 'edit&nbsp;functions') %>
                    </p>    
                </div>
                <div id="CurrentAction">
                    <a id="CancelButton" href="#a">
                        <div id="CancelIcon">
                        </div>
                        <p id="CancelText" class="menuText">
                            <% _t('CANCEL','cancel') %>
                        </p>
                    </a>
                    <a id="ApplyButton" href="#a">
                        <div id="ApplyIcon">
                        </div>
                        <p id="ApplyText" class="menuText">
                            <% _t('APPLY', 'apply') %>
                        </p>
                    </a>
                    <p id="CurrentActionDescription" class="menuText">
                        <% _t('CURRENTACTION', 'current&nbsp;action') %>
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