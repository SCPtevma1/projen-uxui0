const canvas = document.querySelector('#signature-canvas');
const saveButton = document.querySelector('#save-signature');
const clearButton = document.querySelector('#clear-signature');
const message = document.querySelector('#signature-message');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const appBase = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));

if (canvas) {
    const context = canvas.getContext('2d');
    context.lineWidth = 2.5;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.strokeStyle = '#172d50';
    let drawing = false;
    let hasInk = false;

    function point(event) {
        const bounds = canvas.getBoundingClientRect();
        return {
            x: (event.clientX - bounds.left) * (canvas.width / bounds.width),
            y: (event.clientY - bounds.top) * (canvas.height / bounds.height),
        };
    }

    canvas.addEventListener('pointerdown', (event) => {
        drawing = true;
        hasInk = true;
        canvas.setPointerCapture(event.pointerId);
        const position = point(event);
        context.beginPath();
        context.moveTo(position.x, position.y);
    });
    canvas.addEventListener('pointermove', (event) => {
        if (!drawing) return;
        const position = point(event);
        context.lineTo(position.x, position.y);
        context.stroke();
    });
    canvas.addEventListener('pointerup', () => { drawing = false; });
    canvas.addEventListener('pointercancel', () => { drawing = false; });

    clearButton?.addEventListener('click', () => {
        context.clearRect(0, 0, canvas.width, canvas.height);
        hasInk = false;
        message.textContent = '';
    });

    saveButton?.addEventListener('click', async () => {
        if (!hasInk) {
            message.textContent = 'วาดลายเซ็นก่อนบันทึก';
            return;
        }
        saveButton.disabled = true;
        try {
            const response = await fetch(`${appBase}/api/save_signature.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfToken, signature: canvas.toDataURL('image/png') }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error ?? 'บันทึกลายเซ็นไม่สำเร็จ');
            message.textContent = result.message;
        } catch (error) {
            message.textContent = error.message;
        } finally {
            saveButton.disabled = false;
        }
    });
}
