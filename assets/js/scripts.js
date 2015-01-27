(function($) {
	
	var KartenApp = {
		/**
		 * Initialize the app
		 */
		init: function() {
			// Do we have markup on the page?
			var mapWrappers = $('.ktn-wrapper');
			console.log(mapWrappers);

			if (mapWrappers.length < 1) {
				return;
			}

			// Run through each map instance on the page
			mapWrappers.each(function(index, value) {
				var mapId = $(value).find('.ktn-map-canvas').attr('data-ktn-id'),
					mapSettings = window['KartenData' + mapId];

				// Do we have settings data?
				if (mapSettings.length < 1) {
					return;
				}
				else {
					var map = new Map(mapSettings);
				}
				
				// Set infowindow
				// // infoWindow = new google.maps.InfoWindow();
			});
		}
	};

	// Let's go!
	$(document).ready(function() {
		KartenApp.init();
	});

	//////////////////////////////////////////////////////////////////

	// Create template
	function Map(mapSettings) {
		this.data = [];
		this.instagramApiKey = mapSettings.api_keys.instagram;
		this.geocoder = null;
		this.map = null;
		this.myOptions = null;
		this.markersArray = [];
		this.bounds = null;
		this.infowindow = null;
		this.pointsArr = [];
		this.userIDs = [];

		// Getters / Setters
		this.addUserID = function addUserID(id) {
			this.userIDs.push(id);
		};

		this.getUserIDs = function getUserIDs() {
			return this.userIDs;
		};

		// Go get Instagram user IDs
		var deferredIds = this.getInstagramUserIds(mapSettings.usernames, this.instagramApiKey),
			deferredQueries = null,
			scope = this;

		// When all the IDs are back...
		$.when.apply($, deferredIds)
			.then(function() {
				// Create the API endpoint URLs
				scope.constructUrls(mapSettings);

				// Go get Instagram data
				// scope.deferredQueries = scope.retrieveJson(scope.getUserIDs());
			})
			.fail(function() {
				return false;
			});

		// @TO-DO: promise
		var user_cf = this.retrieveJson('https://api.instagram.com/v1/users/2575810/media/recent?count=30&min_timestamp=1352527200&max_timestamp=1354514400&access_token=2575810.b5f685c.afb988a96a2e4267a8f42fe005411afb'); // @openapple
		var user_pf = this.retrieveJson('https://api.instagram.com/v1/users/223256831/media/recent?count=30&min_timestamp=1352527200&max_timestamp=1354514400&access_token=2575810.b5f685c.afb988a96a2e4267a8f42fe005411afb'); // @pfflyer787		

		// Get the map started
		// this.setupMap(**);

		// Set up start point
		startLatLng = new google.maps.LatLng(32.753683,-117.143761); // 4181 Florida Street, San Diego, CA
	
		// Set up end point
		endLatLng = new google.maps.LatLng(39.726486,-104.987536); // 650 N Speer Blvd W, Denver, CO (Towneplace Suites)
		
		// Custom icons
		icon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png";

		// Set up map
		myOptions = {
			zoom: 15,
			center: startLatLng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
	 
		map = new google.maps.Map(document.querySelector('[data-ktn-id="' + mapSettings.id + '"]'), myOptions);

		bounds = new google.maps.LatLngBounds();

		// Set up start marker
		startMarker = new google.maps.Marker({
			map: map,
			position: startLatLng,
			title: 'Start',
			icon: icon
		});
	 
		this.markersArray.push(startMarker);
	 
		// //startMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1); // TO DO: move to admin
	
		// Add each location to bounds
		bounds.extend(startLatLng);
		
		// Set up end marker
		endMarker = new google.maps.Marker({
			map: map,
			position: endLatLng,
			title: 'Finish',
			icon: icon
		});
	 
		this.markersArray.push(endMarker);
	 
		// //endMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);  // TO DO: move to admin
	
		// // Add each location to bounds
		// //bounds.extend(endLatLng);
					
		// Once Instagram query results are obtained, map points
		$.when(deferredQueries)
			.then(function() {
				//addPointsToMap();
			})
			.fail(function() {
				return false;
			});
	}

	/**
	 * Setup the map
	 */
	Map.prototype.setupMap = function(mapData) {
		// Set up start point
		var startLatLng = new google.maps.LatLng(32.753683,-117.143761),
	
			// Set up end point
			endLatLng = new google.maps.LatLng(39.726486,-104.987536),
		
			// Custom icons
			icon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png";

		// Map settings
		myOptions = {
			zoom: 15,
			center: startLatLng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
	 
		// Set the map container
		map = new google.maps.Map(document.querySelector('[data-ktn-id=' + mapData.id + ']'), myOptions);

		// Set the bounds of the map
		bounds = new google.maps.LatLngBounds();

		// Set up start marker
		var startMarker = new google.maps.Marker({
			map: this.mapObj,
			position: startLatLng,
			title: 'Start',
			icon: this.icon
		});
	 
		markersArray.push(startMarker);
	 
		// //startMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1); // TO DO: move to admin
	
		// Add each location to bounds
		bounds.extend(startLatLng);
		
		// Set up end marker
		var endMarker = new google.maps.Marker({
			map: this.mapObj,
			position: endLatLng,
			title: 'Finish',
			icon: this.icon
		});
	 
		markersArray.push(endMarker);
	};

	/**
	 * Retrieve Instagram user IDs
	 */
	Map.prototype.getInstagramUserIds = function(usernames, instagramApiKey) {
		var userIdDeffereds = [],
			scope = this;

		// For each username, retrieve it's ID from Instagram
		$.each(usernames, function(index, name) {
			name = name.trim();
			name = name.toLowerCase();
			var instagramUrl = 'https://api.instagram.com/v1/users/search?q=' + name + '&access_token=' + instagramApiKey + '&count=1';

			userIdDeffereds.push(
				$.ajax({
					url: instagramUrl,
					dataType: 'jsonp'
				})
					.success(function(results) {
						if (results.data.length > 0) {
							var data = results.data[0];
							scope.userIDs[name] = data.id;
						}
					})
			);
		});

		return(userIdDeffereds);
	};

	/**
	 * Create query string and retrive data from Instagram API
	 */
	Map.prototype.constructUrls = function(settings) {
		console.log(settings);
		console.log(this.userIDs);
		var apiUrls = [];

		// Translate Instagram username to ID
		for (var user in this.userIDs) {
			apiUrls.push('https://api.instagram.com/v1/users/' + user.id + '/media/recent?access_token=' + this.instagramApiKey);
		}

		return false;
	};

	/**
	 * Go get the Instagram data
	 */
	Map.prototype.retrieveJson = function(url) {
		// Get JSON
		return $.ajax({
			url: url,
			dataType: 'jsonp',
			cache: false
		});
	};

	/**
	 * Add points to the animateProvider
	 */
	Map.prototype.addPoints = function(mapObj) {
		// Store objects in one array if they have a location set
		// @TO-DO: Clean this up
		for (var i = 0; i < mapObj[0].data.length; i++) {
			// Has location data
			if (mapObj[0].data[i].location !== null) {
				// If has hashtag
				if (mapObj[0].data[i].tags.length > 0 ) {
					// Cycle through all hashtags
					for (var l = 0; l < mapObj[0].data[i].tags.length; l++) {
						// Find if hashtag is #move2012
						if (mapObj[0].data[i].tags[l] == "move2012") {
							// Save to array
							pointsArr.push(mapObj[0].data[i]);
						}
					}
				}
			}
		}
	};

	/**
	 * Add the points to a map
	 */
	Map.prototype.addPointsToMap = function(mapObj1, mapObj2) {
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
		}
		
		// Fix zoom to include all points
		map.fitBounds(bounds);
	};

	/**
	 * Infowindow
	 */
	Map.prototype.listenMarker = function(mapObj, marker) {
		// Get image (medium)
		var addressString = "<img class='ktn-map-img' src='" + mapObj.images.low_resolution.url + "' alt=''/>";
		
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
		
		if (mapObj.caption !== null) {
			captionString = mapObj.caption.text;
		}
		
		// Get location name
		var locationString = "";
		
		if (mapObj.location.name !== null) {
			locationString = mapObj.location.name;
		}
		
		// Infowindow HTML
		var imgString = addressString + '<div class="cf-map-bubble"><span class="cf-map-timestamp">' + dateString + '</span><div class="cf-map-caption">' + captionString + '</div><span class="cf-map-user cf-map-meta">@' + mapObj.user.username + '</span><span class="cf-map-location cf-map-meta">' + locationString + '</span></div>';
		
		// Create infowindow
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.setContent(imgString);
			infowindow.open(map, marker);
		});
	};
	
})(jQuery);