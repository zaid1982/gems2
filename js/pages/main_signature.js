function MainSignature() {
    let isDrawing = false;
    let hasCanvasChanges = false;
    let ctx, canvas;

    function initCanvas() {
        canvas = document.getElementById('sigCanvas');
        resizeCanvas();
        ctx = canvas.getContext('2d');
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111';
        bindCanvasEvents();
        window.addEventListener('resize', function(){ resizeCanvas(true); });
    }

    function resizeCanvas(preserve) {
        const data = preserve ? canvas.toDataURL() : null;
        canvas.width = canvas.parentElement.clientWidth - 2; // minus border
        canvas.height = 160;
        if (preserve && data) {
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0);
            img.src = data;
        }
    }

    function bindCanvasEvents() {
        const start = (e) => {
            e.preventDefault();
            isDrawing = true;
            ctx.beginPath();
            const pos = getPos(e);
            ctx.moveTo(pos.x, pos.y);
        };
        const move = (e) => {
            if(!isDrawing) return; e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasCanvasChanges = true;
            toggleSaveButtons();
        };
        const end = (e) => { if(!isDrawing) return; e.preventDefault(); isDrawing=false; };
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start, {passive:false});
        canvas.addEventListener('touchmove', move, {passive:false});
        canvas.addEventListener('touchend', end);
    }

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;
        if (e.touches && e.touches.length) { clientX = e.touches[0].clientX; clientY = e.touches[0].clientY; }
        else { clientX = e.clientX; clientY = e.clientY; }
        return { x: clientX - rect.left, y: clientY - rect.top };
    }

    function trimCanvas(cnv) {
        const ctx2 = cnv.getContext('2d');
        const w = cnv.width; const h = cnv.height;
        const imgData = ctx2.getImageData(0,0,w,h);
        let top=h, left=w, right=0, bottom=0; let found=false;
        for (let y=0; y<h; y++) {
            for (let x=0; x<w; x++) {
                const idx = (y*w + x)*4;
                if (imgData.data[idx+3] > 0) { // non-transparent
                    if (x < left) left = x;
                    if (x > right) right = x;
                    if (y < top) top = y;
                    if (y > bottom) bottom = y;
                    found = true;
                }
            }
        }
        if(!found) return cnv;
        const trimW = right-left+4; // small padding
        const trimH = bottom-top+4;
        const dest = document.createElement('canvas');
        dest.width = Math.min(400, trimW); // scale down if very wide
        dest.height = (trimH * dest.width) / trimW;
        dest.getContext('2d').drawImage(cnv, left-2, top-2, trimW, trimH, 0, 0, dest.width, dest.height);
        return dest;
    }

    function canvasToPayload() {
        let c = canvas;
        if (document.getElementById('chkTrim').checked) {
            c = trimCanvas(canvas);
        }
        return c.toDataURL('image/png');
    }

    function toggleSaveButtons() {
        document.getElementById('btnSaveCanvas').disabled = !hasCanvasChanges;
    }

    function resetCanvas() {
        ctx.clearRect(0,0,canvas.width, canvas.height);
        hasCanvasChanges = false;
        toggleSaveButtons();
    }

    function loadExisting() {
        try {
            const userId = mzGetUserId();
            if (!userId) {
                toastr['error']('User not authenticated');
                return;
            }
            
            const displayElement = document.getElementById('signature-display');
            const deleteButton = document.getElementById('delete-signature');
            
            if (!displayElement) {
                console.warn('signature-display element not found');
                return;
            }
            
            const data = mzAjaxRequest2('user_signature/'+userId,'GET');
            if (data && data.file_path) {
                displayElement.innerHTML = `<img src="${data.file_path}" class="img-fluid border" style="max-height: 200px;">`;
                if (deleteButton) deleteButton.style.display = 'block';
            } else {
                displayElement.innerHTML = '<p class="text-muted">No signature saved</p>';
                if (deleteButton) deleteButton.style.display = 'none';
            }
        } catch(e) { 
            const displayElement = document.getElementById('signature-display');
            const deleteButton = document.getElementById('delete-signature');
            
            if (displayElement) {
                displayElement.innerHTML = '<p class="text-muted">No signature saved</p>';
            }
            if (deleteButton) {
                deleteButton.style.display = 'none';
            }
        }
    }

    function saveCanvas() {
        try {
            const userId = mzGetUserId();
            if (!userId) {
                toastr['error']('User not authenticated');
                return;
            }
            
            const base64 = canvasToPayload();
            // Convert base64 to the format expected by uploadDocument
            const arr = base64.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const b64Data = arr[1];
            const binaryData = atob(b64Data);
            
            const uploadDetails = {
                name: 'signature',
                filename: 'signature.png',
                size: binaryData.length,
                type: mime,
                data: b64Data
            };
            
            const resp = mzAjaxRequest2('user_signature/'+userId, 'POST', uploadDetails);
            toastr['success']('Signature saved');
            hasCanvasChanges = false;
            toggleSaveButtons();
            loadExisting();
        } catch(e) { toastr['error'](e.message); }
    }

    function saveFile() {
        try {
            const userId = mzGetUserId();
            if (!userId) {
                toastr['error']('User not authenticated');
                return;
            }
            
            const fileInput = document.getElementById('fileSignature');
            if (!fileInput.files.length) {
                toastr['warning']('Please select a file');
                return;
            }
            
            const file = fileInput.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    // Extract base64 data (remove data:mime;base64, prefix)
                    const base64Data = e.target.result.split(',')[1];
                    
                    const uploadDetails = {
                        name: 'signature',
                        filename: file.name,
                        size: file.size,
                        type: file.type,
                        data: base64Data
                    };
                    
                    const resp = mzAjaxRequest2('user_signature/'+userId, 'POST', uploadDetails);
                    toastr['success']('Signature uploaded');
                    fileInput.value = '';
                    loadExisting();
                } catch(uploadError) {
                    toastr['error'](uploadError.message);
                }
            };
            
            reader.onerror = function() {
                toastr['error']('Error reading file');
            };
            
            reader.readAsDataURL(file);
        } catch(e) { 
            toastr['error'](e.message); 
        }
    }

    function deleteSignature() {
        if(!confirm('Delete saved signature?')) return;
        try {
            const userId = mzGetUserId();
            if (!userId) {
                toastr['error']('User not authenticated');
                return;
            }
            
            // Note: DELETE not implemented in existing endpoint, would need backend update
            toastr['warning']('Delete feature requires backend implementation');
            // Future: mzAjaxRequest2('user_signature/'+userId,'DELETE');
        } catch(e) { toastr['error'](e.message); }
    }

    this.init = function() {
        initCanvas();
        loadExisting();
        $('#btnClearCanvas').on('click', function(){ resetCanvas(); });
        $('#btnSaveCanvas').on('click', saveCanvas);
        $('#fileSignature').on('change', function(){ $('#btnSaveFile').prop('disabled', !this.files.length); });
        $('#btnSaveFile').on('click', saveFile);
        $('#delete-signature').on('click', deleteSignature);
    };
}

$(document).ready(function(){
    const mainSig = new MainSignature();
    mainSig.init();
});
