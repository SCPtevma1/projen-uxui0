let uploadedImageData = "";
function getCategoryLabel(cat) {
    switch (cat) {
        case 'main': return 'จานหลัก';
        case 'dessert': return 'ของหวาน';
        case 'drink': return 'เครื่องดื่ม';
        case 'appetizer': return 'ของทานเล่น';
        default: return 'ทั่วไป';
    }
}
const foodImageInput = document.getElementById('foodImage');
if (foodImageInput) {
    foodImageInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                uploadedImageData = event.target.result;
                document.getElementById('imagePreview').innerHTML = `<img src="${uploadedImageData}" alt="Preview">`;
            };
            reader.readAsDataURL(file);
        }
    });
}

function addIngredientInput() {
    const container = document.getElementById('ingredientsList');
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'dynamic-item';
    div.innerHTML = `
        <input type="text" class="ingredient-input" placeholder="ใส่ชื่อวัตถุดิบและปริมาณ" required>
        <button type="button" class="btn-remove" onclick="removeItem(this)"><i class="fa-solid fa-trash"></i></button>
    `;
    container.appendChild(div);
}
function addStepInput() {
    const container = document.getElementById('stepsList');
    if (!container) return;
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

function removeItem(btn) {
    const item = btn.parentElement;
    const parent = item.parentElement;
    item.remove();
    if (parent.id === 'stepsList') {
        updateStepNumbers();
    }
}

function updateStepNumbers() {
    const steps = document.querySelectorAll('#stepsList .step-num');
    steps.forEach((numSpan, index) => {
        numSpan.innerText = index + 1;
    });
}
const recipeForm = document.getElementById('recipeForm');
if (recipeForm) {
    recipeForm.addEventListener('submit', function (e) {
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
            cal: cal.includes('Kcal') ? cal : `${cal} Kcal`,
            image: uploadedImageData || 'https://placehold.co/600x400/111827/f8fafc?text=Recipe+Image',
            ingredients,
            steps
        };

        let recipes = JSON.parse(localStorage.getItem('myRecipes') || '[]');
        recipes.push(newRecipe);
        localStorage.setItem('myRecipes', JSON.stringify(recipes));

        alert('บันทึกสูตรอาหารเรียบร้อยแล้ว!');
        this.reset();
        const preview = document.getElementById('imagePreview');
        if (preview) preview.innerHTML = '';
        uploadedImageData = "";
        loadStoredRecipes();
    });
}
function loadStoredRecipes() {
    const grid = document.getElementById('recipeCardsGrid');
    if (!grid) return;

    const recipes = JSON.parse(localStorage.getItem('myRecipes') || '[]');
    if (recipes.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 30px;">ยังไม่มีสูตรอาหารที่บันทึกไว้ เริ่มต้นกรอกฟอร์มด้านบนเพื่อบันทึกสูตรแรกของคุณได้เลย!</p>';
        return;
    }

    grid.innerHTML = recipes.map(recipe => `
        <div class="card">
            <div class="card-img-wrapper">
                <span class="badge badge-${recipe.category}">${getCategoryLabel(recipe.category)}</span>
                <img src="${recipe.image}" alt="${recipe.name}">
            </div>
            <div class="card-body">
                <div class="card-header-row">
                    <h3 class="card-title">${recipe.name}</h3>
                    <span class="card-price">${recipe.price}</span>
                </div>
                <div class="card-meta-row">
                    <span class="card-cal"><i class="fa-solid fa-fire"></i> ${recipe.cal}</span>
                </div>
                <p class="card-text"><strong>วัตถุดิบ:</strong> ${recipe.ingredients.join(', ')}</p>
                <p class="card-text"><strong>ขั้นตอน (${recipe.steps.length} ขั้นตอน):</strong> ${recipe.steps[0] || '-'}</p>
            </div>
        </div>
    `).join('');
}
document.addEventListener('DOMContentLoaded', () => {
    updateStepNumbers();
    loadStoredRecipes();
});
