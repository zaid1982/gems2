function GoogleMapsDrawingPolygon () {

    let map;
    let drawingManager;
    let selectedShape;
    let selectedCoordinate = [];
    let self = this;

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

    this.setDrawingManager = function (_mapId, _center) {
        try {
            mzCheckFuncParam([_mapId]);
            const centerLat = 2.728;
            const centerLng = 101.898;
            map = new google.maps.Map(document.getElementById(_mapId), {
                center: {lat: centerLat, lng: centerLng},
                zoom: 18,
            });
            self.deleteSelectedShape();
            drawingManager.setMap(map);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };

    this.drawPolygon = function (_mapId) {
        try {
            mzCheckFuncParam([_mapId]);
            const triangleCoords = [
                { lat: 2.9286482470098343, lng: 101.89708804893495 },
                { lat: 2.928642889598061, lng: 101.89715778636933 },
                { lat: 2.928530383945045, lng: 101.89714705753327 },
                { lat: 2.9286482470098343, lng: 101.89708804893495 }
            ];
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