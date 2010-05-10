<li class='$EvenOdd'>
<% control Fields %>$FieldHolder<% end_control %>
<div class='item-set-field-item-actions'><% control Actions %>$Field<% end_control %></div>
<% if DefaultAction %><a class='item-set-field-action' href='$DefaultAction.Link'>$Label</a><% else %>$Label<% end_if %> 
</li>
