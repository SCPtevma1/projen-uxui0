        document.addEventListener('DOMContentLoaded', () => {
            const shareBtn = document.getElementById('shareBtn');
            const toastContainer = document.getElementById('toast-container');

            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                let bgClass = type === 'success' ? 'bg-green-600' : 'bg-gray-800';
                let icon = type === 'success' ? '<i class="ph-fill ph-check-circle text-lg"></i>' : '<i class="ph-fill ph-info text-lg"></i>';
                toast.className = `animate-toast-enter flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl text-white text-sm font-medium ${bgClass} pointer-events-auto`;
                toast.innerHTML = `${icon} <span>${message}</span>`;
                toastContainer.appendChild(toast);
                setTimeout(() => {
                    toast.classList.remove('animate-toast-enter');
                    toast.classList.add('animate-toast-leave');
                    setTimeout(() => {
                        toast.remove();
                    }, 300); // Matches animation duration
                }, 3000);
            }

            // Web Share API implementation
            shareBtn.addEventListener('click', async () => {
                const shareData = {
                    title: 'โปรไฟล์ของฉัน - ช่องทางการติดต่อ',
                    text: 'ดูช่องทางการติดต่อทั้งหมดและผลงานของฉันได้ที่นี่!',
                    url: window.location.href
                };
                if (navigator.share) {
                    try {
                        await navigator.share(shareData);
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            console.error('Error sharing:', err);
                            showToast('เกิดข้อผิดพลาดในการแชร์', 'error');
                        }
                    }
                } else {
                    try {
                        const tempInput = document.createElement('input');
                        tempInput.value = window.location.href;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                        
                        showToast('คัดลอกลิงก์โปรไฟล์แล้ว!', 'success');
                    } catch (err) {
                        console.error('Failed to copy: ', err);
                        showToast('ไม่สามารถคัดลอกลิงก์ได้', 'error');
                    }
                }
            });
        });