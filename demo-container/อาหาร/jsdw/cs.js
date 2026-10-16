// Select DOM elements
const filterButtons = document.querySelectorAll("#filter-buttons .btn-filter");
const filterableCards = document.querySelectorAll("#filterable-cards .card");

// Function to filter cards based on category
const filterCards = (e) => {
    // Target button element
    const clickedBtn = e.currentTarget;
    
    // Update active button state
    document.querySelector("#filter-buttons .active").classList.remove("active");
    clickedBtn.classList.add("active");

    const selectedFilter = clickedBtn.dataset.filter;

    // Filter matching cards
    filterableCards.forEach(card => {
        if (card.dataset.name === selectedFilter || selectedFilter === "all") {
            card.classList.remove("hide");
            card.classList.add("show");
        } else {
            card.classList.add("hide");
            card.classList.remove("show");
        }
    });
};

// Add click listener to all filter buttons
filterButtons.forEach(button => button.addEventListener("click", filterCards));

// Trigger filter directly from Navigation Bar links
function filterFromNav(category) {
    const targetBtn = document.querySelector(`#filter-buttons button[data-filter="${category}"]`);
    if (targetBtn) {
        targetBtn.click();
        
        // Smooth scroll to card container
        document.querySelector(".header-section").scrollIntoView({ behavior: "smooth" });
    }
}

// Modal DOM elements
const modal = document.getElementById("detailModal");
const modalImg = document.getElementById("modalImage");
const modalTitle = document.getElementById("modalTitle");
const modalPrice = document.getElementById("modalPrice");
const modalCal = document.getElementById("modalCal");
const modalDesc = document.getElementById("modalDesc");

// Function to open Modal with selected item data
function openDetailModal(button) {
    const card = button.closest(".card");
    
    // Extract dataset from card
    modalImg.src = card.dataset.img;
    modalTitle.textContent = card.dataset.title;
    modalPrice.textContent = card.dataset.price;
    modalCal.innerHTML = `<i class="fa-solid fa-fire"></i> พลังงาน: ${card.dataset.cal}`;
    modalDesc.textContent = card.dataset.desc;

    // Show modal
    modal.classList.add("active");
}

// Function to close Modal
function closeDetailModal() {
    modal.classList.remove("active");
}

// Close modal when clicking on backdrop
window.addEventListener("click", (e) => {
    if (e.target === modal) {
        closeDetailModal();
    }
});