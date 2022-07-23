function GoogleMapsDrawingPolygon () {

    let map;
    let drawingManager;
    let selectedShape;
    let selectedCoordinate = [];
    let mapMarkers = [];
    let self = this;

    this.addMarker = function (_latitude, _longitude, _title, _address, _label, _alt) {
        try {
            if (_latitude === null || _longitude === null) {
                return false;
            }
            const svgMarker = typeof _alt !== 'undefined' ? {
                path: "M942.080 366.080v-409.6h-307.2v81.92h-266.24v-81.92h-286.72v409.6zM756.716 440.73c-11.756 51.384-38.277 96.563-80.077 131.092-40.489 33.649-88.392 40.018-143.954 60.498h-0.205v129.597c0 0.573 5.55 1.024 10.732 2.888 7.352 2.56 14.295 5.509 23.491 8.704 9.032 3.052 17.203 5.612 25.723 7.414 8.356 1.987 14.152 2.826 18.125 2.826 7.721 0 17.49-1.229 28.918-3.727 11.633-2.826 24.023-5.878 36.618-9.339 13.128-3.973 24.842-6.984 36.209-9.544 10.834-2.478 19.907-3.502 27.423-3.502 11.96-0.020 18.043 1.638 38.523 5.284v148.316c-20.48-4.874-24.105-7.557-38.543-7.557-7.516 0-16.814 1.147-27.668 3.891-11.325 2.171-22.999 5.55-36.127 9.359-12.595 3.83-24.494 6.717-35.492 9.134-11.121 2.642-18.35 3.912-26.214 3.912-26.071 0-61.235-6.164-81.715-17.531v27.075h-40.96v-307.2h-1.29c-55.132-20.48-103.404-28.017-143.852-61.665-41.001-34.57-67.625-76.82-80.22-128.225 0 0-39.26 5.571-39.26-35.389h572.826c0 40.96-43.008 33.69-43.008 33.69z",
                fillColor: "red",
                fillOpacity: 0.8,
                strokeWeight: 0,
                rotation: 180,
                scale: 0.03,
                anchor: new google.maps.Point(15, 30)
            }: '';
            const label = typeof _label !== 'undefined' ? _label : '';
            const marker = new google.maps.Marker({
                position: new google.maps.LatLng(parseFloat(_latitude), parseFloat(_longitude)),
                map: map,
                title: _title,
                label: label,
                icon: svgMarker
            });
            const contentString =
                '<div id="content">' +
                '<div id="siteNotice">' +
                "</div>" +
                '<h5 id="firstHeading" class="firstHeading">'+_title+'</h5>' +
                '<div id="bodyContent">' +
                "<p>"+(_address !== null ? _address : '')+"</p>" +
                "</div>" +
                "</div>";
            const infowindow = new google.maps.InfoWindow({
                content: contentString,
            });
            marker.addListener("click", () => {
                infowindow.open({
                    anchor: marker,
                    map,
                    shouldFocus: false,
                });
            });
            mapMarkers.push(marker);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.clearMarkers = function () {
        try {
            if (mapMarkers) {
                for (let i in mapMarkers) {
                    mapMarkers[i].setMap(null);
                }
                mapMarkers.length = 0;
            }
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.centerMap = function () {
        try {
            if (mapMarkers) {
                let bounds = new google.maps.LatLngBounds();
                for (let i = 0; i < mapMarkers.length; i++) {
                    const latLng = new google.maps.LatLng(mapMarkers[i].getPosition().lat(), mapMarkers[i].getPosition().lng());
                    console.log(latLng)
                    bounds.extend(latLng);
                }
                map.fitBounds(bounds, 1);
            }
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.clearSelection = function () {
        if (selectedShape) {
            selectedShape.setEditable(false);
            selectedShape = null;
        }
    };

    this.setSelection = function (shape) {
        self.clearSelection();
        selectedShape = shape;
        shape.setEditable(true);
        //selectColor(shape.get('fillColor') || shape.get('strokeColor'));
    };

    this.deleteSelectedShape = function () {
        if (selectedShape) {
            selectedShape.setMap(null);
            selectedCoordinate = [];
            // To show:
            drawingManager.setOptions({
                drawingControl: true
            });
        }
    };

    this.initMapDrawing = function (_mapId) {
        try {
            map = new google.maps.Map(document.getElementById(_mapId), {
                center: {lat: 2.728, lng: 101.898},
                zoom: 15,
            });

            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: [
                        //google.maps.drawing.OverlayType.MARKER,
                        //google.maps.drawing.OverlayType.CIRCLE,
                        google.maps.drawing.OverlayType.POLYGON,
                        //google.maps.drawing.OverlayType.POLYLINE,
                        //google.maps.drawing.OverlayType.RECTANGLE,
                    ],
                }
            });

            google.maps.event.addListener(drawingManager, 'overlaycomplete', function (e) {
                if (e.type !== google.maps.drawing.OverlayType.MARKER) {
                    // Switch back to non-drawing mode after drawing a shape.
                    drawingManager.setDrawingMode(null);
                    // To hide:
                    drawingManager.setOptions({
                        drawingControl: false
                    });
                    // Add an event listener that selects the newly-drawn shape when the user mouse down on it.
                    let newShape = e.overlay;
                    newShape.type = e.type;
                    google.maps.event.addListener(newShape, 'click', function () {
                        self.setSelection(newShape);
                    });
                    self.setSelection(newShape);
                }
            });

            google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
                const path = polygon.getPath();
                let coordinates = [];
                for (let i = 0; i < path.length; i++) {
                    coordinates.push({
                        lat: path.getAt(i).lat(),
                        lng: path.getAt(i).lng()
                    });
                }
                selectedCoordinate = coordinates;
            });

            // Clear the current selection when the drawing mode is changed, or when the
            // map is clicked.
            google.maps.event.addListener(drawingManager, 'drawingmode_changed', function () {
                self.clearSelection();
            });
            google.maps.event.addListener(map, 'click', function () {
                self.clearSelection();
            });
            self.deleteSelectedShape();

            drawingManager.setMap(map);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.setDrawingManager = function (_mapId, _latitude, _longitude, _zoom) {
        try {
            mzCheckFuncParam([_mapId]);
            const centerLat = typeof _latitude !== 'undefined' ? parseFloat(_latitude) : 2.9280622799459395;
            const centerLng = typeof _longitude !== 'undefined' ? parseFloat(_longitude) : 101.89702233481408;
            const mapZoom = typeof _zoom !== 'undefined' ? parseInt(_zoom) : 19;
            self.clearMarkers();
            map = new google.maps.Map(document.getElementById(_mapId), {
                center: {lat: centerLat, lng: centerLng},
                zoom: mapZoom
            });
            self.deleteSelectedShape();
            drawingManager.setMap(map);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.drawPolygon = function (_mapId, _coordinates) {
        try {
            mzCheckFuncParam([_mapId]);
            let triangleCoords = [];
            if (typeof _coordinates !== 'undefined' && $.isArray(_coordinates)) {
                for (let i = 0; i < _coordinates.length; i++) {
                    const pointSide = { lat: _coordinates[i][0], lng: _coordinates[i][1]};
                    triangleCoords.push(pointSide);
                    if (i === _coordinates.length - 1) {
                        const pointOrigin = { lat: _coordinates[0][0], lng: _coordinates[0][1]};
                        triangleCoords.push(pointOrigin);
                    }
                }
            }
            const bermudaTriangle = new google.maps.Polygon({
                paths: triangleCoords,
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
            });
            bermudaTriangle.setMap(map);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.setDrawingControl = function (_mode) {
        try {
            drawingManager.setOptions({
                drawingControl: _mode
            });
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.getSelectedCoordinate = function () {
        return selectedCoordinate;
    };

    this.getMapCenter = function () {
        return map.getCenter().toString();
    };

    this.getZoomLevel = function () {
        return map.getZoom();
    };
}