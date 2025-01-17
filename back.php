<?php
// config.php - Database configuration
$host = 'localhost';
$dbname = 'watch_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Create necessary tables if they don't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// add_to_cart.php - Handle adding items to cart
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid(); // Simple user identification
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    
    if ($product_id) {
        // Check if product already exists in cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing_item = $stmt->fetch();
        
        if ($existing_item) {
            // Update quantity if product already in cart
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $existing_item['id']]);
        } else {
            // Add new item to cart
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
        }
        
        echo json_encode(['success' => true]);
    }
}

// get_cart.php - Retrieve cart items
function getCartItems($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// cart.php - Display cart page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="cart-container">
        <h2>Your Shopping Cart</h2>
        <?php
        $cart_items = getCartItems($_SESSION['user_id']);
        $total = 0;
        
        if (empty($cart_items)): ?>
            <p>Your cart is empty</p>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                ?>
                <div class="cart-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                        <p>Subtotal: $<?php echo number_format($subtotal, 2); ?></p>
                        <button onclick="removeFromCart(<?php echo $item['id']; ?>)">Remove</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-total">
                <h3>Total: $<?php echo number_format($total, 2); ?></h3>
                <button onclick="checkout()">Proceed to Checkout</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Updated JavaScript code for cart functionality
    function addToCart(productId, quantity = 1) {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                alert('Product added to cart!');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function removeFromCart(cartItemId) {
        if (confirm('Are you sure you want to remove this item?')) {
            fetch('remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_item_id=${cartItemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    function updateCartCount() {
        fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            document.querySelector('.cart-count').textContent = data.count;
        })
        .catch(error => console.error('Error:', error));
    }

    function checkout() {
        // Implement checkout functionality
        alert('Checkout functionality will be implemented here');
    }
    </script>
</body>
</html>

<?php
// remove_from_cart.php - Handle removing items from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = $_POST['cart_item_id'] ?? null;
    
    if ($cart_item_id) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_item_id, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true]);
    }
}

// get_cart_count.php - Get the number of items in cart
header('Content-Type: application/json');
$stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$result = $stmt->fetch();

echo json_encode(['count' => $result['count'] ?? 0]);
?>