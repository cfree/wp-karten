(function($) {
	
	var geocoder;
	var map;
	var myOptions;
	var markersArray = [];
	var bounds;
	var infowindow = new google.maps.InfoWindow();
	var pointsArr = [];
	
	$(document).ready(function(){
		initialize();
	});
	
    function initialize() {
		
		// Set up start point - TO DO: move to admin
		var startLatLng = new google.maps.LatLng(32.753683,-117.143761); // 4181 Florida Street, San Diego, CA
	
		// Set up end point - TO DO: move to admin
		var endLatLng = new google.maps.LatLng(39.726486,-104.987536); // 650 N Speer Blvd W, Denver, CO (Towneplace Suites)
		
		// Custom icons - TO DO: move to admin
		var purpleIcon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png";
		
		// Set up map
			myOptions = {
			 	zoom: 15, // TO DO: move to admin
				center: startLatLng,
			 	mapTypeId: google.maps.MapTypeId.ROADMAP
			};
		 
			map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);

			bounds = new google.maps.LatLngBounds();

		// Set up start marker
			var startMarker = new google.maps.Marker({
			 	map: map,
			 	position: startLatLng,
				title: 'Start',
				icon: purpleIcon // TO DO: move to admin
			});
		 
			markersArray.push(startMarker);
		 
			//startMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1); // TO DO: move to admin
		
			// Add each location to bounds
			bounds.extend(startLatLng);
		
		// Set up end marker
			var endMarker = new google.maps.Marker({
			 	map: map,
			 	position: endLatLng,
				title: 'Finish',
				icon: purpleIcon
			});
		 
			markersArray.push(endMarker);
		 
			//endMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);  // TO DO: move to admin
		
			// Add each location to bounds
			//bounds.extend(endLatLng);
		
		// Show instagram posts	between November 10, 2012 and December 3, 2012  // TO DO: move to admin
		var user_cf = cf_getJSON('https://api.instagram.com/v1/users/2575810/media/recent?count=30&min_timestamp=1352527200&max_timestamp=1354514400&access_token=2575810.b5f685c.afb988a96a2e4267a8f42fe005411afb'); // @openapple
		var user_pf = cf_getJSON('https://api.instagram.com/v1/users/223256831/media/recent?count=30&min_timestamp=1352527200&max_timestamp=1354514400&access_token=2575810.b5f685c.afb988a96a2e4267a8f42fe005411afb'); // @pfflyer787
		
		// Once both objects are obtained, map points
		$.when(
			user_cf,
			user_pf
		).then(cf_map_points);
		
	} // initialize
	
	function cf_getJSON(url) {
		// Get JSON
		return $.ajax({
	        type: "GET",
	        dataType: "jsonp",
	        cache: false,
	        url: url
		});
 	}
	
	function addPoints(mapObj) {
		// Store objects in one array if they have a location set
		for(var i = 0; i < mapObj[0].data.length; i++) {
			// Has location data
			if(mapObj[0].data[i].location != null) {
				// If has hashtag
				if(mapObj[0].data[i].tags.length > 0 ) {
					// Cycle through all hashtags
					for(var l = 0; l < mapObj[0].data[i].tags.length; l++) {
						// Find if hashtag is #move2012
						if(mapObj[0].data[i].tags[l] == "move2012") {
							// Save to array
							pointsArr.push(mapObj[0].data[i]);
						} // if
					} // for
				} // if
			} // if
		} // for
	}
	
	// Map points
	function cf_map_points(mapObj1, mapObj2) {
		// Add the points to a single array
		addPoints(mapObj1);
		addPoints(mapObj2);
		
		// Create points for each element in object
		for(var j = 0; j < pointsArr.length; j++) {
			// Create location from lat and lng
			var location = new google.maps.LatLng(pointsArr[j].location.latitude, pointsArr[j].location.longitude);
	
			// Add to bounds
			bounds.extend(location);
			
			// Create our "tiny" marker icon
			var mapIcon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/purple-dot.png";
			
			var locMarker = new google.maps.Marker({
	        	map: map, 
	        	position: location,
				icon: mapIcon
	    	});
	
			// Add to array
			markersArray.push(locMarker);
	
			// Add infowindow
			listenMarker(pointsArr[j], locMarker);

		} // for
		
		// Fix zoom to include all points
		map.fitBounds(bounds);
	} // cf_map_points
	
	// Infowindow
	function listenMarker(mapObj, marker) {
		// Get image (medium)
		var addressString = "<img class='cf-map-img' src='" + mapObj.images.low_resolution.url + "' alt=''/>";
		
		// Parse date information
		var date = new Date(mapObj.created_time * 1000);
		var month = date.getMonth();
		var day = date.getDate();
		var year = date.getFullYear();
		var hours = date.getHours();
		var minutes = date.getMinutes();
		
		// Create date string
		var dateString = (month + 1) + "/" + day + "/" + year + " " + hours + ":" + minutes + "hrs";
		
		// Get caption
		var captionString = "";
		
		if(mapObj.caption != null) {
			captionString = mapObj.caption.text;
		}
		
		// Get location name
		var locationString = "";
		
		if(mapObj.location.name != null) {
			locationString = mapObj.location.name;
		}
		
		// Infowindow HTML
		var imgString = addressString + '<div class="cf-map-bubble"><span class="cf-map-timestamp">' + dateString + '</span><div class="cf-map-caption">' + captionString + '</div><span class="cf-map-user cf-map-meta">@' + mapObj.user.username + '</span><span class="cf-map-location cf-map-meta">' + locationString + '</span></div>';
		
		// Create infowindow
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.setContent(imgString);
		  	infowindow.open(map, marker);
		});
	}
	
})(jQuery);