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
                console.log(coordinates);
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

    this.getSelectedCoordinate = function () {
        return selectedCoordinate;
    };

    this.setDrawingManager = function (_mapId) {
        try {
            mzCheckFuncParam([_mapId]);
            map = new google.maps.Map(document.getElementById(_mapId), {
                center: {lat: 2.928, lng: 101.898},
                zoom: 15,
            });
            self.deleteSelectedShape();
            drawingManager.setMap(map);
        } catch (e) {
            throw new Error(_ALERT_MSG_ERROR_DEFAULT);
        }
    };
}