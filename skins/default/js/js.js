window.onload = function(){
	Chart.defaults.global.responsive = true;

	// Get context with jQuery - using jQuery's .get() method.
	var p_rain_area = document.getElementById("soo_p_rain").getContext("2d");
	// This will get the first returned node in the jQuery collection.
	var p_rain_graph = new Chart(p_rain_area).Line(p_rain_data, p_rain_option);


	// Get context with jQuery - using jQuery's .get() method.
	var temperature_area = document.getElementById("soo_temperature").getContext("2d");
	// This will get the first returned node in the jQuery collection.
	var temperature_graph = new Chart(temperature_area).Line(temperature_data, temperature_option);

	// Get context with jQuery - using jQuery's .get() method.
	var humidity_area = document.getElementById("soo_humidity").getContext("2d");
	// This will get the first returned node in the jQuery collection.
	var humidity_graph = new Chart(humidity_area).Line(humidity_data, humidity_option);
}