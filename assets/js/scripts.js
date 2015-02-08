(function($) {
	
	var KartenApp = {
		/**
		 * Initialize the app
		 */
		init: function() {
			// Do we have markup on the page?
			var mapWrappers = $('.ktn-wrapper');

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
					console.log(mapSettings);
					var mapObj = new Map(mapSettings);
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
		this.apiUrls = [];
		this.apiResults = [];

		var settings = mapSettings;

		// Getters / Setters
		this.getSettings = function getSettings(settings) {
			return mapSettings;
		};

		// Go get Instagram user IDs
		var deferredIds = this.getInstagramUserIds(mapSettings.usernames, this.instagramApiKey),
			scope = this;

		// When all the IDs are back...
		$.when.apply($, deferredIds)
			.then(function() {
				// Create the API endpoint URLs
				scope.constructUrls(mapSettings);
				console.log(scope.apiUrls);

				// Go get Instagram data
				var deferredQueries = scope.retrieveJson();

				// Once Instagram query results are obtained, map points
				$.when.apply($, deferredQueries)
					.then(function() {
						//scope.addPointsToMap();
						console.log(scope.apiResults);
					});
			})
			.fail(function() {
				return false;
			});

		// Get the map started
		this.setupMap();
	}

	/**
	 * Set the map up
	 */
	Map.prototype.setupMap = function() {
		// Map settings
		var mapSettings = this.getSettings(),

			// Set up start point
			startLatLng = new google.maps.LatLng(32.753683,-117.143761), // 4181 Florida Street, San Diego, CA
	
			// Set up end point
			endLatLng = new google.maps.LatLng(39.726486,-104.987536), // 650 N Speer Blvd W, Denver, CO (Towneplace Suites)
		
			// Custom icons
			icon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png";

		// Set up map
		this.myOptions = {
			zoom: 15,
			center: startLatLng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
	 
		this.map = new google.maps.Map(document.querySelector('[data-ktn-id="' + mapSettings.id + '"]'), this.myOptions);

		this.bounds = new google.maps.LatLngBounds();

		// Set up start marker
		var startMarker = new google.maps.Marker({
			map: this.map,
			position: startLatLng,
			title: 'Start',
			icon: icon
		});
	 
		this.markersArray.push(startMarker);
	 
		startMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
	
		// Add each location to bounds
		this.bounds.extend(startLatLng);
		
		// Set up end marker
		var endMarker = new google.maps.Marker({
			map: this.map,
			position: endLatLng,
			title: 'Finish',
			icon: icon
		});
	 
		this.markersArray.push(endMarker);
	 
		endMarker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
	
		// Add each location to bounds
		this.bounds.extend(endLatLng);
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
	 * Create endpoint URL for use with Instagram API
	 */
	Map.prototype.constructUrls = function(settings) {
		var queryString = '';

		// Start date
		if (typeof settings.start_date === 'string') {
			queryString += '&min_timestamp=' + settings.start_date;
		}

		// End date
		if (typeof settings.end_date === 'string') {
			queryString += '&max_timestamp=' + settings.end_date;
		}

		// Number of posts
		if (typeof settings.end_date === 'string') {
			queryString += '&count=' + settings.max_posts;
		}

		// Create endpoint URL
		for (var user in settings.usernames) {
			this.apiUrls.push('https://api.instagram.com/v1/users/' + this.userIDs[settings.usernames[user]] + '/media/recent?access_token=' + this.instagramApiKey + queryString);
		}
	};

	/**
	 * Go get the Instagram data
	 */
	Map.prototype.retrieveJson = function() {
		var dataDeffereds = [],
			scope = this;

		$.each(this.apiUrls, function(index, url) {
			dataDeffereds.push(
				$.ajax({
					url: url,
					dataType: 'jsonp',
					cache: false
				})
					.success(function(results) {
						if (typeof results.data === 'array' && results.data.length > 0) {
							scope.apiResults.push(results.data);
						}
					})
			);
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
		var addressString = "<img class='ktn-map-img' src='" + mapObj.images.low_resolution.url + "' alt=''/>",
		
			// Parse date information
			date = new Date(mapObj.created_time * 1000),
			month = date.getMonth(),
			day = date.getDate(),
			year = date.getFullYear(),
			hours = date.getHours(),
			minutes = date.getMinutes(),
		
			// Create date string
			dateString = (month + 1) + "/" + day + "/" + year + " " + hours + ":" + minutes + "hrs",
		
			// Get caption
			captionString = "";
		
		if (mapObj.caption !== null) {
			captionString = mapObj.caption.text;
		}
		
		// Get location name
		var locationString = "";
		
		if (mapObj.location.name !== null) {
			locationString = mapObj.location.name;
		}
		
		// Infowindow HTML
		var imgString = addressString + '<div class="ktn-map-bubble"><span class="ktn-map-timestamp">' + dateString + '</span><div class="ktn-map-caption">' + captionString + '</div><span class="ktn-map-user ktn-map-meta">@' + mapObj.user.username + '</span><span class="ktn-map-location ktn-map-meta">' + locationString + '</span></div>';
		
		// Create infowindow
		google.maps.event.addListener(marker, 'click', function() {
			infowindow.setContent(imgString);
			infowindow.open(map, marker);
		});
	};
	
})(jQuery);