<?php
declare(strict_types=1);

$db = new PDO('mysql: host=localhost;dbname=products_db','root', '9Ao29e4P',[]);
$query = "CREATE TABLE IF NOT EXISTS Category (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    Name TEXT
);)";

$stmt = $db->prepare($query);
$stmt->execute();

$query = "CREATE TABLE IF NOT EXISTS User (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    Name TEXT
);)";
$stmt = $db->prepare($query);
$stmt->execute();

$query = "CREATE TABLE IF NOT EXISTS Product (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    Name TEXT,
    Price FLOAT,
    categoryId INT,
    FOREIGN KEY (categoryId) REFERENCES Category(id)
);)";

$stmt = $db->prepare($query);
$stmt->execute();

$query = "CREATE TABLE IF NOT EXISTS Cart (
    id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    userId INT,
    productId INT,
    FOREIGN KEY (userId) REFERENCES User(id),
    FOREIGN KEY (productId) REFERENCES Product(id)
);";

$stmt = $db->prepare($query);
$stmt->execute();

for($i=1; $i<=10; $i++)
{
    InsertIntoUserOrCategory($db,"User{$i}", "User");
}

for($i=1; $i<=10; $i++)
{
    InsertIntoUserOrCategory($db,"Category{$i}", "Category");
}

for($i=1; $i<=10; $i++)
{
    InsertIntoProduct($db, "Product{$i}", $i, $i);
}

for($i=1; $i<=10; $i++)
{
    InsertIntoCart($db, $i, 11-$i);
}

function InsertIntoUserOrCategory(PDO $db,string $name, string $table_name)
{
    $query = "INSERT INTO {$table_name} (name) VALUES (:name)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name',$name);
    $stmt->execute();
}

function InsertIntoProduct(PDO $db, string $name, float $price, int $categoryId)
{
    $query = "INSERT INTO Product (Name, Price, categoryId) VALUES (:name, :price, :categoryId)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':price', $price);
    $stmt->bindValue(':categoryId', $categoryId);
    $stmt->execute();
}

function InsertIntoCart(PDO $db, int $userId,int $productId )
{
    $query = "INSERT INTO Cart (userId, productId) VALUES (:userId, :productId)";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->bindValue(':productId', $productId);
    $stmt->execute();
}

function ShowAllUsers(PDO $db)
{
    $query = 'SELECT * FROM User';
    $stmt = $db->prepare($query);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Всі користувачі: <br>";
    foreach ($users as $user) {
        echo "ID: " . $user['id'] . ", Ім'я: " . $user['Name'] . "<br>";
    }
}
ShowAllUsers($db);

function ShowAllItemsInCart(PDO $db)
{
    $query = 'SELECT Cart.id, User.name AS user_name, Product.Name AS product_name, Category.Name AS category_name, Product.Price
              FROM Cart
              INNER JOIN User ON Cart.userId = User.id
              INNER JOIN Product ON Cart.productId = Product.id
              INNER JOIN Category ON Product.categoryId = Category.id';
    $stmt = $db->prepare($query);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo "User: " . $item['user_name'] . "<br>";
        echo "Product: " . $item['product_name'] . "<br>";
        echo "Category: " . $item['category_name'] . "<br>";
        echo "Price: " . $item['Price'] . "<br>";
        echo "<br>";
    }
}
echo "<br>Вся корзина: <br>";
ShowAllItemsInCart($db);

function ShowAllItemsInCartUser(PDO $db, int $userId)
{
    $query = "SELECT Cart.id, User.name AS user_name, Product.Name AS product_name, Category.Name AS category_name, Product.Price
              FROM Cart
              INNER JOIN User ON Cart.userId = User.id
              INNER JOIN Product ON Cart.productId = Product.id
              INNER JOIN Category ON Product.categoryId = Category.id 
              WHERE User.id = :userId";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo "User: " . $item['user_name'] . "<br>";
        echo "Product: " . $item['product_name'] . "<br>";
        echo "Category: " . $item['category_name'] . "<br>";
        echo "Price: " . $item['Price'] . "<br>";
        echo "<br>";
    }
}
echo "<br>Корзина користувача з id 6: <br>";
ShowAllItemsInCartUser($db,6);

function GetCategoriesAddedToCartByUser(PDO $db, int $userId)
{
    $query = "SELECT DISTINCT Category.Name AS category_name
              FROM Cart
              INNER JOIN Product ON Cart.productId = Product.id
              INNER JOIN Category ON Product.categoryId = Category.id
              WHERE Cart.userId = :userId";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo $item['category_name'] . "<br>";
    }
}

echo "<br>Категорії, продукти яких добавив користкувач з id 6 в корзину: <br>";
GetCategoriesAddedToCartByUser($db,6);

function GetUsersWhoBoughtProduct(PDO $db, int $productId)
{
    $query = "SELECT DISTINCT User.*
              FROM Cart
              INNER JOIN User ON Cart.userId = User.id
              WHERE Cart.productId = :productId";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':productId', $productId);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        echo "ID: " . $item['id'] . ", Name: " . $item['Name'] . "<br>";
    }
}

echo "<br>Користувачі, які купили товар з id 6: <br>";
GetUsersWhoBoughtProduct($db,6);

function GetCategoriesNotInUserCart(PDO $db, int $userId)
{
    $query = "SELECT Category.id AS category_id, Category.Name AS category_name, Product.id AS product_id, Product.Name AS product_name
              FROM Category
              LEFT JOIN Product ON Category.id = Product.categoryId AND Product.id NOT IN (
                  SELECT productId FROM Cart WHERE userId = :userId
              )";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        if ($item['product_id']) {
            echo "Category ID: " . $item['category_id'] . ", Category Name: " . $item['category_name'] . "<br>";
            echo "Product ID: " . $item['product_id'] . ", Product Name: " . $item['product_name'] . "<br>";
            echo "<br>";
        }
    }
}

echo "<br>категорії, продуктів якої немає в користувача з id 6 в корзині: <br>";
GetCategoriesNotInUserCart($db,6);