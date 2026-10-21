let uploadedImageData = "";

// อ่านไฟล์รูปภาพและแปลงเป็น Base64
document.getElementById('foodImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            uploadedImageData = event.target.result;
            document.getElementById('imagePreview').innerHTML = `<img src="${uploadedImageData}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    }
});

// ฟังก์ชันเพิ่มช่องกรอกวัตถุดิบ
function addIngredientInput() {
    const container = document.getElementById('ingredientsList');
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <input type="text" class="ingredient-input" placeholder="ใส่ชื่อวัตถุดิบและปริมาณ" required>
        <button type="button" class="btn-remove" onclick="removeItem(this)"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}

// ฟังก์ชันเพิ่มช่องกรอกขั้นตอน
function addStepInput() {
    const container = document.getElementById('stepsList');
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <span class="step-num"></span>
        <input type="text" class="step-input" placeholder="ระบุขั้นตอนการทำ" required>
        <button type="button" class="btn-remove" onclick="removeItem(this)"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
    updateStepNumbers();
}

// ลบแถววัตถุดิบหรือขั้นตอน
function removeItem(btn) {
    const item = btn.parentElement;
    const parent = item.parentElement;
    item.remove();
    if (parent.id === 'stepsList') {
        updateStepNumbers();
    }
}

// อัปเดตตัวเลขลำดับขั้นตอน
function updateStepNumbers() {
    const steps = document.querySelectorAll('#stepsList .step-num');
    steps.forEach((numSpan, index) => {
        numSpan.innerText = index + 1;
    });
}

// บันทึกข้อมูลสูตรอาหารลง localStorage
document.getElementById('recipeForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const name = document.getElementById('foodName').value;
    const category = document.getElementById('foodCategory').value;
    const price = document.getElementById('foodPrice').value || "0";
    const cal = document.getElementById('foodCal').value || "-";

    const ingredients = Array.from(document.querySelectorAll('.ingredient-input')).map(input => input.value);
    const steps = Array.from(document.querySelectorAll('.step-input')).map(input => input.value);

    const newRecipe = {
        id: Date.now(),
        name,
        category,
        price: `฿${price}`,
        cal,
        image: uploadedImageData || 'https://placehold.co/600x400/1e293b/f8fafc?text=No+Image',
        ingredients,
        steps
    };

    let recipes = JSON.parse(localStorage.getItem('myRecipes') || '[]');
    recipes.push(newRecipe);
    localStorage.setItem('myRecipes', JSON.stringify(recipes));

    alert('บันทึกสูตรอาหารเรียบร้อยแล้ว!');
    this.reset();
    document.getElementById('imagePreview').innerHTML = '';
    uploadedImageData = "";
    loadStoredRecipes();
});

// โหลดสูตรอาหารมาแสดงผล
function loadStoredRecipes() {
    const grid = document.getElementById('recipeCardsGrid');
    if (!grid) return;

    const recipes = JSON.parse(localStorage.getItem('myRecipes') || '[]');
    grid.innerHTML = recipes.map(recipe => `
        <div class="card">
            <div class="card-img-wrapper">
                <img src="${recipe.image}" alt="${recipe.name}">
            </div>
            <div class="card-body">
                <div class="card-header-row">
                    <h3 class="card-title">${recipe.name}</h3>
                    <span class="card-price">${recipe.price}</span>
                </div>
                <p class="card-text"><strong>วัตถุดิบ:</strong> ${recipe.ingredients.join(', ')}</p>
                <p class="card-text"><strong>ขั้นตอน (${recipe.steps.length} ขั้นตอน):</strong> ${recipe.steps[0]}...</p>
            </div>
        </div>
    `).join('');
}

// เรียกทำงานเมื่อโหลดหน้าเว็บ
document.addEventListener('DOMContentLoaded', () => {
    updateStepNumbers();
    loadStoredRecipes();
});