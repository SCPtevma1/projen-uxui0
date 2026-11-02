const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const uploadForm = document.querySelector('#upload-form');
const uploadMessage = document.querySelector('#upload-message');
const orderForm = document.querySelector('#order-form');
const orderMessage = document.querySelector('#order-message');
const priceSummary = document.querySelector('#price-summary');
const shippingAddressField = document.querySelector('#shipping-address-field');
const settings = window.printDocPricing ?? {};
const appBase = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
let pdfjsPromise;

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...payload, csrf_token: csrfToken }),
    });
    const result = await response.json();
    if (!response.ok) {
        throw new Error(result.error ?? 'ดำเนินการไม่สำเร็จ');
    }
    return result;
}

async function readPdfPages(file) {
    pdfjsPromise ??= import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.min.mjs');
    const pdfjsLib = await pdfjsPromise;
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs';
    const pdf = await pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
    return pdf.numPages;
}

if (uploadForm) {
    uploadForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const files = [...uploadForm.querySelector('input[type="file"]').files];
        if (!files.length) return;
        const submitButton = uploadForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        uploadMessage.textContent = 'กำลังอัปโหลดและตรวจสอบไฟล์...';
        const errors = [];
        let uploadedCount = 0;

        for (const file of files) {
            const body = new FormData();
            body.append('csrf_token', csrfToken);
            body.append('documents[]', file);
            try {
                const response = await fetch(`${appBase}/api/upload.php`, {
                    method: 'POST',
                    body,
                });
                const result = await response.json();
                if (!response.ok || !result.documents?.length) {
                    throw new Error(result.error ?? 'อัปโหลดไม่สำเร็จ');
                }
                const document = result.documents[0];
                uploadedCount++;
                if (document.type === 'pdf') {
                    try {
                        const pages = await readPdfPages(file);
                        await postJson(`${appBase}/api/count_pages.php`, {
                            document_id: document.id,
                            pages,
                            source: 'pdf',
                        });
                    } catch (error) {
                        errors.push(`${file.name}: นับหน้า PDF อัตโนมัติไม่ได้ (${error.message}) กรุณากรอกจำนวนหน้าเอง`);
                    }
                }
            } catch (error) {
                errors.push(`${file.name}: ${error.message}`);
            }
        }

        if (uploadedCount > 0) {
            sessionStorage.setItem('printDocUploadErrors', errors.join(' · '));
            window.location.assign(`${window.location.pathname}?uploaded=1`);
        } else if (errors.length) {
            uploadMessage.textContent = errors.join(' · ');
            submitButton.disabled = false;
        }
    });
}

const previousUploadErrors = sessionStorage.getItem('printDocUploadErrors');
if (previousUploadErrors) {
    uploadMessage.textContent = previousUploadErrors;
    sessionStorage.removeItem('printDocUploadErrors');
}

function selectedPages() {
    return [...document.querySelectorAll('.document-select:checked')]
        .reduce((sum, checkbox) => sum + Number(checkbox.dataset.pages || 0), 0);
}

function estimateOrder() {
    if (!priceSummary || !orderForm) return;
    const pages = selectedPages();
    if (!pages) {
        priceSummary.innerHTML = '<span>เลือกเอกสารที่ระบุจำนวนหน้าแล้วเพื่อดูราคา</span>';
        return;
    }
    const values = new FormData(orderForm);
    const copies = Math.max(1, Number(values.get('copies')) || 1);
    const count = pages * copies;
    const color = values.get('color');
    let rate = color === 'color' ? Number(settings.color_rate) : Number(settings.bw_rate);
    if (color === 'bw' && count >= Number(settings.bulk_threshold)) {
        rate = Number(settings.bulk_bw_rate);
    }
    const paperFees = {
        a4_70: 0,
        a3_70: settings.paper_a3,
        b5_70: settings.paper_b5,
        a4_80: settings.paper_80gsm,
        a4_card: settings.paper_card,
    };
    const bindingFees = {
        none: 0,
        staple: settings.binding_staple,
        spiral: settings.binding_spiral,
        coil: settings.binding_coil,
    };
    const printing = count * rate;
    const paper = count * Number(paperFees[values.get('paper')]);
    const binding = copies * Number(bindingFees[values.get('binding')]);
    const shipping = values.get('delivery') === 'shipping' ? Number(settings.shipping_fee) : 0;
    const subtotal = printing + paper + binding + shipping;
    const total = Math.max(Number(settings.minimum_order), subtotal);
    priceSummary.innerHTML = `
        <div><span>จำนวนหน้ารวม</span><strong>${count.toLocaleString('th-TH')} หน้า</strong></div>
        <div><span>ค่าพิมพ์และกระดาษ</span><strong>฿${(printing + paper).toFixed(2)}</strong></div>
        <div><span>ค่าเข้าเล่มและจัดส่ง</span><strong>฿${(binding + shipping).toFixed(2)}</strong></div>
        <div class="price-total"><span>ยอดประมาณการ</span><strong>฿${total.toFixed(2)}</strong></div>
        ${total === Number(settings.minimum_order) ? '<small>รวมยอดขั้นต่ำต่อคำสั่งซื้อแล้ว</small>' : ''}
    `;
}

document.querySelectorAll('.document-select').forEach((input) => input.addEventListener('change', estimateOrder));
orderForm?.addEventListener('input', estimateOrder);
orderForm?.addEventListener('change', (event) => {
    if (event.target.name === 'delivery') {
        shippingAddressField.hidden = event.target.value !== 'shipping';
        const addressInput = shippingAddressField.querySelector('textarea');
        addressInput.required = event.target.value === 'shipping';
        if (!addressInput.required) addressInput.value = '';
    }
    estimateOrder();
});

document.querySelectorAll('.save-pages').forEach((button) => {
    button.addEventListener('click', async () => {
        const input = document.querySelector(`.page-input[data-document-id="${button.dataset.documentId}"]`);
        const pages = Number(input.value);
        if (!Number.isInteger(pages) || pages < 1 || pages > 10000) {
            input.setCustomValidity('กรอกจำนวนหน้าตั้งแต่ 1 ถึง 10,000');
            input.reportValidity();
            return;
        }
        input.setCustomValidity('');
        button.disabled = true;
        try {
            await postJson(`${appBase}/api/count_pages.php`, {
                document_id: Number(button.dataset.documentId),
                pages,
                source: 'manual',
            });
            window.location.reload();
        } catch (error) {
            uploadMessage.textContent = error.message;
            button.disabled = false;
        }
    });
});

orderForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    orderMessage.textContent = '';
    const documentIds = [...document.querySelectorAll('.document-select:checked')].map((checkbox) => Number(checkbox.value));
    if (!documentIds.length) {
        orderMessage.textContent = 'เลือกเอกสารที่ต้องการพิมพ์ก่อน';
        return;
    }
    const formData = new FormData(orderForm);
    const submitButton = orderForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    try {
        const result = await postJson(`${appBase}/api/create_order.php`, {
            document_ids: documentIds,
            copies: Number(formData.get('copies')),
            options: {
                color: formData.get('color'),
                paper: formData.get('paper'),
                binding: formData.get('binding'),
                delivery: formData.get('delivery'),
                shipping_address: formData.get('shipping_address'),
            },
        });
        orderMessage.textContent = `สร้างคำสั่งพิมพ์ #${result.order_id} แล้ว · ยอดรวม ฿${Number(result.totals.total).toFixed(2)}`;
        window.setTimeout(() => window.location.assign(`${appBase}/user/dashboard.php`), 1800);
    } catch (error) {
        orderMessage.textContent = error.message;
        submitButton.disabled = false;
    }
});

estimateOrder();
