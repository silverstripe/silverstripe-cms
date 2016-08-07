function hover_over() {
	Element.addClassName(this, 'over');
}
function hover_out() {
	Element.removeClassName(this, 'over');
}

hover_behaviour = {
	onmouseover : hover_over,
	onmouseout : hover_out
}

