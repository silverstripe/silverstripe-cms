<script>
// Define a dataset.
var dataset = {
	<% control DataSets %>
	'$SetName':		
	'myFirstDataset': 	[[0, 3], [1, 2], [2, 1.414], [3, 2.3]],
	'mySecondDataset': 	[[0, 1.4], [1, 2.67], [2, 1.34], [3, 1.2]],
	'myThirdDataset': 	[[0, 0.46], [1, 1.45], [2, 1.0], [3, 1.6]],
	'myFourthDataset': 	[[0, 0.3], [1, 0.83], [2, 0.7], [3, 0.2]]
	<% end_control %>
};

// Define options.
var options = {
	// Define a padding for the canvas node
	padding: {left: 30, right: 0, top: 10, bottom: 30},

	// Background color to render.
	backgroundColor: '#f2f2f2',

	// Use the predefined blue colorscheme.
	colorScheme: '$colorScheme',

	// Set the labels.
   	xTicks: [
		{v:0, label:'January'},
      		{v:1, label:'February'},
      		{v:2, label:'March'},
      		{v:3, label:'April'}
	]
};

<% if $chartType = line %>
	var chart = new Plotr.LineChart('$',options);
<% else_if $chartType = bar %>
	var chart = new Plotr.BarChart('$',options);
<% else_if $chartType = pie %>
	var chart = new Plotr.PieChart('$',options);
<% end_if %>

chart.addDataset(dataset);

chart.render();
</script>
