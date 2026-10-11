        function openOrderModal(name, price) {
            document.getElementById('modal-package-name').innerText = name;
            document.getElementById('modal-package-price').innerText = price + ' บาท';
            
            document.getElementById('modal-step-1').classList.remove('hidden');
            document.getElementById('modal-step-2').classList.add('hidden');
            document.getElementById('order-modal').classList.remove('hidden');
        }

        function closeOrderModal() {
            document.getElementById('order-modal').classList.add('hidden');
        }

        function submitOrder(e) {
            e.preventDefault();
            document.getElementById('modal-step-1').classList.add('hidden');
            document.getElementById('modal-step-2').classList.remove('hidden');
        }