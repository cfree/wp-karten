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
				var mapInstance = $(value).find('.ktn-map-canvas'),
					mapId = mapInstance.attr('data-ktn-id'),
					mapSettings = window['KartenData' + mapId];

				// Do we have settings data? Is the mapSettings.map already in use? (Indicates more than one usage of an ID)
				if (typeof mapSettings === 'undefined') {
					console.log('mapSettings of ' + mapId + ' not found');
					return;
				}
				else if (typeof mapSettings !== 'undefined' && typeof mapSettings.map !== 'undefined') {
					$(value).hide();
					console.log('Map ID #' + mapId + ' is already in use on the page. Hiding all subsequent instances.');
					return;
				}
				else {
					window['KartenData' + mapId].map = new KartenMap(mapSettings);
				}
			});
		}
	};

	// Let's go!
	$(document).ready(function() {
		KartenApp.init();
	});

	// Create template
	function KartenMap(mapSettings) {
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
		this.settings = mapSettings;
		this.infowindow = null;

		// Go get Instagram user IDs
		var deferredIds = this.getInstagramUserIds(),
			scope = this;

		// When all the IDs are back...
		$.when.apply($, deferredIds)
			.then(function() {
				// Create the API endpoint URLs
				scope.constructUrls();
			})
			.then(function() {
				// Go get Instagram data
				var deferredQueries = scope.retrieveJson();

				// Once Instagram query results are obtained, map points
				$.when.apply($, deferredQueries)
					.then(function() {
						// Get the map started
						scope.setupMap();
					});
			})
			.fail(function() {
				return false;
			});
	}

	/**
	 * Set the map up
	 */
	KartenMap.prototype.setupMap = function() {
		// Add the rest of the data points to the map
		var pointsResult = this.addPoints();

		if (pointsResult) {
			var scope = this,
				icon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png",
				startMarker = false,
				startLatLng,
				endMarker = false,
				endLatLng,
				geocoder = new google.maps.Geocoder();

			// Set up map
			this.myOptions = {
				zoom: 15,
				center: this.settings.startLatLng,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
		 
			this.map = new google.maps.Map(document.querySelector('[data-ktn-id="' + this.settings.id + '"]'), this.myOptions);

			this.bounds = new google.maps.LatLngBounds();

			// Addresses?
			if (this.settings.start_addr) {
				geocoder.geocode( { 'address': this.settings.start_addr }, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						startLatLng = results[0].geometry.location;

						// Add start location to bounds
						scope.bounds.extend(startLatLng);

						// Set up start marker
						startMarker = new google.maps.Marker({
							map: scope.map,
							position: startLatLng,
							title: 'Start',
							icon: icon
						});

						scope.markersArray.push(startMarker);

						scope.map.fitBounds(scope.bounds);
					}
				});
			}

			if (this.settings.end_addr) {
				geocoder.geocode( { 'address': this.settings.end_addr }, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						endLatLng = results[0].geometry.location;

						// Add end location to bounds
						scope.bounds.extend(endLatLng);

						// Set up start marker
						endMarker = new google.maps.Marker({
							map: scope.map,
							position: endLatLng,
							title: 'Finish',
							icon: icon
						});

						scope.markersArray.push(endMarker);

						scope.map.fitBounds(scope.bounds);
					}
				});
			}

			$('[data-ktn-id="' + this.settings.id + '"]').parent('.ktn-wrapper').addClass('ktn-show');

			this.addPointsToMap();
		}
	};

	/**
	 * Add points to the list of points
	 */
	KartenMap.prototype.addPoints = function() {
		var mapWrapper = this.apiResults,
			maps = mapWrapper[0];

		for (var map in maps) {
			// Has location data?
			if (maps[map].location) {
				// Is hashtag needed?
				if (this.settings.hashtags) {
					if (maps[map].tags !== undefined && maps[map].tags.length > 0) {
						var tags = maps[map].tags;

						// Cycle through all hashtags
						for (var tag in tags) {
							// Find desired hashtag
							if (tags[tag] === this.settings.hashtags) {
								// Save to array
								this.pointsArr.push(maps[map]);
							}
						}
					}
				}
				// No hashtag needed
				else {
					// Save to array
					this.pointsArr.push(maps[map]);
				}
			}
		}

		if (this.pointsArr.length > 0) {
			return true;
		}
		else {
			console.log('No relevant posts found');
			return false;
		}
	};

	/**
	 * Add the points to a map
	 */
	KartenMap.prototype.addPointsToMap = function() {
		// Create points for each element in object
		for (var point in this.pointsArr) {
			// Create location from lat and lng
			var location = new google.maps.LatLng(
				this.pointsArr[point].location.latitude,
				this.pointsArr[point].location.longitude
			);
	
			// Add to bounds
			this.bounds.extend(location);
			
			// Create our "tiny" marker icon
			var mapIcon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/purple-dot.png";
			
			var locMarker = new google.maps.Marker({
				map: this.map,
				position: location,
				icon: mapIcon
			});
	
			// Add to array
			this.markersArray.push(locMarker);
	
			// Add infowindow
			this.listenMarker(this.pointsArr[point], locMarker);
		}
		
		// Fix zoom to include all points
		this.map.fitBounds(this.bounds);
	};

	/**
	 * Retrieve Instagram user IDs
	 */
	KartenMap.prototype.getInstagramUserIds = function() {
		var userIdDeffereds = [],
			scope = this;

		// Are there any usernames?
		if (scope.settings.usernames) {

			// For each username, retrieve it's ID from Instagram
			$.each(this.settings.usernames, function(index, name) {
				name = name.trim();
				name = name.toLowerCase();
				var instagramUrl = 'https://api.instagram.com/v1/users/search?q=' + name + '&access_token=' + scope.instagramApiKey + '&count=1';

				userIdDeffereds.push(
					$.ajax({
						url: instagramUrl,
						dataType: 'jsonp'
					})
						.success(function(results) {
							if (results.data.length > 0) {
								scope.userIDs[name] = results.data[0].id;
							}
						})
				);
			});
		}

		return(userIdDeffereds);
	};

	/**
	 * Create endpoint URL for use with Instagram API
	 */
	KartenMap.prototype.constructUrls = function() {
		var queryString = '';

		// Start date
		if (typeof this.settings.start_date === 'string') {
			queryString += '&min_timestamp=' + this.settings.start_date;
		}

		// End date
		if (typeof this.settings.end_date === 'string') {
			queryString += '&max_timestamp=' + this.settings.end_date;
		}

		// Number of posts
		if (typeof this.settings.max_posts === 'string') {
			queryString += '&count=' + this.settings.max_posts;
		}

		// Create endpoint URL
		if (this.settings.usernames) {
			// We have users 
			for (var user in this.settings.usernames) {
				this.apiUrls.push('https://api.instagram.com/v1/users/' + this.userIDs[this.settings.usernames[user]] + '/media/recent?access_token=' + this.instagramApiKey + queryString);
			}
		} else if(this.settings.hashtags) {
			// No users set, search for hasthtag
			this.apiUrls.push('https://api.instagram.com/v1/tags/' + this.settings.hashtags + '/media/recent?access_token=' + this.instagramApiKey + queryString);
		} else {
			console.log('No username or hasthtag set.');
			return;
		}
	};

	/**
	 * Go get the Instagram data
	 */
	KartenMap.prototype.retrieveJson = function() {
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
						if (results.data.length > 0) {
							scope.apiResults.push(results.data);
						}
					})
			);
		});

		return dataDeffereds;
	};

	/**
	 * Infowindow
	 */
	KartenMap.prototype.listenMarker = function(mapObj, marker) {
		var scope = this;

		// Get location name
		var locationString = '';
		
		if (mapObj.location.name !== undefined) {
			locationString = mapObj.location.name;
		}

		// Get image (medium)
		var addressString = '<img class="ktn-map-img" src="' + mapObj.images.low_resolution.url + '" alt="' + locationString + '"/>',
		
			// Parse date information
			date = new Date(mapObj.created_time * 1000),
			month = date.getMonth(),
			day = date.getDate(),
			year = date.getFullYear(),
			hours = date.getHours(),
			minutes = date.getMinutes(),
		
			// Create date string
			dateString = (month + 1) + '/' + day + '/' + year + ' ' + hours + ':' + minutes + 'hrs',
		
			// Get caption
			captionString = '';
		
		if (mapObj.caption !== null) {
			captionString = mapObj.caption.text;
		}
		
		// Infowindow HTML
		var imgString = addressString + '<div class="ktn-map-bubble"><span class="ktn-map-timestamp">' + dateString + '</span><div class="ktn-map-caption">' + captionString + '</div><span class="ktn-map-user ktn-map-meta">@' + mapObj.user.username + '</span><span class="ktn-map-location ktn-map-meta">' + locationString + '</span></div>';
		
		// Create infowindow
		google.maps.event.addListener(marker, 'click', function() {
			// Close infowindow if one is open
			if (scope.infowindow) {
				scope.infowindow.close();
			}

			// Set infowindow
			scope.infowindow = new google.maps.InfoWindow();
			scope.infowindow.setContent(imgString);
			scope.infowindow.open(scope.map, marker);
		});
	};
	
})(jQuery);