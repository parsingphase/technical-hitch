/**
 * Created by wechsler on 17/01/2016.
 */

var mapController = {
  locations: {},
  markers: {},
  map: null,

  drawLocationsMap: function(mapCentre, scale, locations) {
    if (!scale) {
      scale = 14;
    }

    if (!locations) {
      locations = [];
    }

    if (this.map) {
      this.map.remove();
    }

    L.mapbox.accessToken = 'Add token from mapbox.com here';

    // eg:     L.mapbox.accessToken = 'pk.eyJ1IjoijkladjkalfjioEFJIOHFJASDLHJp896379478erwYTB3OW0wZmJicjU1eWEifQ.vk0vlN2ue6KfWpsj0_iWBA';

    var map = L.mapbox.map('map', 'mapbox.streets')
      .setView(mapCentre, scale);

    for (var i = 0; i < locations.length; i++) {
      var location = locations[i];
      this.locations[location.title] = location;
      var cssIcon = this.makeIcon(location.title);

      var options = {icon: cssIcon, riseOnHover: true};
      if (location.primary) {
        options.zIndexOffset = 200;
      }
      this.markers[location.title] = L.marker(location.position, options).addTo(map);
    }
    this.map = map;
    return map;
  },

  focusPointer: function(pointerName) {
    for (var title in this.markers) {
      if (this.markers.hasOwnProperty(title)) {
        var location = this.locations[title];
        var offset = 0;
        if (location && location.primary) {
          offset = 200;
        }
        this.markers[title].setZIndexOffset(offset).setIcon(this.makeIcon(title));
      }
    }
    this.markers[pointerName].setZIndexOffset(150).setIcon(this.makeIcon(pointerName, 'selected'));
    this.map.setView(this.markers[pointerName].getLatLng(), 15, {animate: true});
  },

  makeIcon: function(title, extraClass) {
    var width = 120, height = 30;
    if (title.length > 15) {
      var extraChars = title.length - 15;
      width += (extraChars * 6);
    }

    var location = this.locations[title];
    if (location && location.primary) {
      extraClass = extraClass + ' primary';
    }

    return L.divIcon({
      // Specify a class name we can refer to in CSS.
      className: 'mapMarker',
      // Set marker width and height
      iconSize: [width, height],
      iconAnchor: [width / 2, height],
      html: '<div class="placeMarker' + (extraClass ? ' ' + extraClass : '') + '">' +
      '<div class="title">' + title + '</div>' +
      '<div class="pointer"><span class="fa fa-caret-down"></span></div>' +
      '</div>'
    });
  }
};
