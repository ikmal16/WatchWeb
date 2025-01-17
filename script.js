// Sample product data
const products = [
    {
        id: 1,
        name: "Classic Gold Watch",
        price: 999.99,
        image: "/api/placeholder/300/200"
    },
    {
        id: 2,
        name: "Silver Chronograph",
        price: 1299.99,
        image: "/api/placeholder/300/200"
    },
    {
        id: 3,
        name: "Diamond Collection",
        price: 2499.99,
        image: "/api/placeholder/300/200"
    },
    {
        id: 4,
        name: "Sport Edition",
        price: 799.99,
        image: "/api/placeholder/300/200"
    }
];

// Cart functionality
let cartCount = 0;
const cartCountElement = document.querySelector('.cart-count');

// Function to create product cards
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.innerHTML = `
        <img src="${product.image}" alt="${product.name}" class="product-image">
        <div class="product-info">
            <h3 class="product-title">${product.name}</h3>
            <p class="product-price">$${product.price}</p>
            <button class="add-to-cart" onclick="addToCart(${product.id})">Add to Cart</button>
        </div>
    `;
    return card;
}

// Function to add products to cart
function addToCart(productId) {
    cartCount++;
    cartCountElement.textContent = cartCount;
    
    // Animation for cart icon
    cartCountElement.style.transform = 'scale(1.2)';
    setTimeout(() => {
        cartCountElement.style.transform = 'scale(1)';
    }, 200);
}

// Render products
const productsContainer = document.querySelector('.products');
products.forEach(product => {
    productsContainer.appendChild(createProductCard(product));
});

// Smooth scroll for navigation
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});