<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$action = $_GET['action'] ?? '';

if ($action === 'health') {
    require_once __DIR__ . '/../config/database.php';
    db();
    json_response(['ok' => true, 'service' => APP_NAME]);
}

if ($action === 'public') {
    require_once __DIR__ . '/../config/database.php';
    $pdo = db();
    json_response([
        'menu' => $pdo->query('SELECT id,name,category,price,available,image_path FROM menu_items ORDER BY id')->fetchAll(),
        'categories' => $pdo->query('SELECT DISTINCT category FROM menu_items ORDER BY category')->fetchAll(PDO::FETCH_COLUMN),
        'delivery_zones' => $pdo->query('SELECT id,name,fee FROM delivery_zones WHERE active = 1 ORDER BY name')->fetchAll(),
    ]);
}

if ($action === 'delivery-zones') {
    require_once __DIR__ . '/../config/database.php';
    require_method('GET');
    json_response(['delivery_zones' => db()->query('SELECT id,name,fee FROM delivery_zones WHERE active = 1 ORDER BY name')->fetchAll()]);
}

if ($action === 'order') {
    require_once __DIR__ . '/../config/database.php';
    require_method('POST');
    $input = request_json();
    $customerName = clean_text($input['name'] ?? '', 100);
    $phone = clean_phone($input['phone'] ?? '');
    $email = clean_email($input['email'] ?? '');
    if (empty(trim((string)($input['order_type'] ?? '')))) {
        json_response(['error' => 'Order service is required.'], 422);
    }
    if (!is_array($input['items']) || count($input['items']) === 0) {
        json_response(['error' => 'Select at least one menu item.'], 422);
    }
    if (!in_array($input['order_type'], ['Pickup', 'Delivery', 'Dine-in', 'Catering'], true)) {
        json_response(['error' => 'Invalid order type.'], 422);
    }
    $paymentMethod = (string)($input['payment_method'] ?? 'Cash');
    if (!in_array($paymentMethod, ['Cash', 'Mobile Money', 'Card'], true)) {
        json_response(['error' => 'Invalid payment method.'], 422);
    }
    $pdo = db();
    enforce_rate_limit($pdo, 'order-create', 10, 300);
    $deliveryFee = 0;
    $deliveryZoneId = null;
    if ($input['order_type'] === 'Delivery') {
        if (trim((string)($input['address'] ?? '')) === '') {
            json_response(['error' => 'A delivery address is required.'], 422);
        }
        $deliveryZoneId = filter_var($input['delivery_zone_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($deliveryZoneId === false) {
            json_response(['error' => 'A valid delivery zone is required.'], 422);
        }
        $zoneStatement = $pdo->prepare('SELECT fee FROM delivery_zones WHERE id = ? AND active = 1');
        $zoneStatement->execute([$deliveryZoneId]);
        $zone = $zoneStatement->fetch();
        if (!$zone) {
            json_response(['error' => 'The selected delivery zone is unavailable.'], 422);
        }
        $deliveryFee = (int)$zone['fee'];
    }
    $prices = [];
    $statement = $pdo->prepare('SELECT id,price FROM menu_items WHERE id = ? AND available = 1');
    foreach ($input['items'] as $item) {
        $quantity = filter_var($item['quantity'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        if ($quantity === false) json_response(['error' => 'Invalid item quantity.'], 422);
        $statement->execute([(int)($item['id'] ?? 0)]);
        $menuItem = $statement->fetch();
        if (!$menuItem) json_response(['error' => 'One selected item is unavailable.'], 422);
        $prices[] = ['id' => (int)$menuItem['id'], 'quantity' => $quantity, 'price' => (int)$menuItem['price']];
    }
    $pdo->beginTransaction();
    try {
        $subtotal = array_sum(array_map(fn ($item) => $item['quantity'] * $item['price'], $prices));
        $total = $subtotal + $deliveryFee;
        $reference = 'MMB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $accessToken = bin2hex(random_bytes(20));
        $statement = $pdo->prepare('INSERT INTO orders (customer_name,phone,email,order_type,address,notes,subtotal,delivery_fee,payment_method,delivery_zone_id,total,order_reference,access_token_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $addressValue = trim((string)($input['address'] ?? ''));
        $address = $addressValue === '' ? '' : clean_text($addressValue, 300);
        $notes = trim((string)($input['notes'] ?? ''));
        if (mb_strlen($notes) > 1000) json_response(['error' => 'Order notes must be 1,000 characters or fewer.'], 422);
        $statement->execute([$customerName, $phone, $email, $input['order_type'], $address, $notes, $subtotal, $deliveryFee, $paymentMethod, $deliveryZoneId, $total, $reference, password_hash($accessToken, PASSWORD_DEFAULT)]);
        $orderId = (int)$pdo->lastInsertId();
        $statement = $pdo->prepare('INSERT INTO order_items (order_id,menu_item_id,quantity,unit_price) VALUES (?,?,?,?)');
        foreach ($prices as $item) $statement->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        $pdo->commit();
        json_response(['ok' => true, 'id' => $orderId, 'reference' => $reference, 'token' => $accessToken, 'subtotal' => $subtotal, 'delivery_fee' => $deliveryFee, 'total' => $total, 'payment_method' => $paymentMethod], 201);
    } catch (Throwable $error) {
        $pdo->rollBack();
        error_log($error->getMessage());
        json_response(['error' => 'Unable to create the order.'], 500);
    }
}

if ($action === 'message') {
    require_once __DIR__ . '/../config/database.php';
    require_method('POST');
    enforce_rate_limit(db(), 'message', 10, 300);
    $input = request_json();
    $name = clean_text($input['name'] ?? '', 100);
    $email = clean_email($input['email'] ?? '', true);
    $message = clean_text($input['message'] ?? '', 2000);
    $subject = clean_text($input['subject'] ?? 'Website enquiry', 150);
    $statement = db()->prepare('INSERT INTO messages (name,email,subject,message) VALUES (?,?,?,?)');
    $statement->execute([$name, $email, $subject, $message]);
    json_response(['ok' => true, 'id' => (int)db()->lastInsertId()], 201);
}

if ($action === 'subscribe') {
    require_once __DIR__ . '/../config/database.php';
    require_method('POST');
    enforce_rate_limit(db(), 'subscribe', 10, 300);
    $input = request_json();
    $email = clean_email($input['email'] ?? '', true);
    $statement = db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)');
    $statement->execute([$email]);
    json_response(['ok' => true], 201);
}

if ($action === 'login') {
    require_once __DIR__ . '/../config/auth.php';
    require_method('POST');
    require_csrf();
    enforce_rate_limit(db(), 'login', 10, 300);
    $input = request_json();
    $statement = db()->prepare('SELECT id,email,password_hash,role FROM admins WHERE email = ?');
    $statement->execute([clean_email($input['email'] ?? '', true)]);
    $admin = $statement->fetch();
    if (!$admin || !password_verify((string)($input['password'] ?? ''), $admin['password_hash'])) {
        json_response(['error' => 'Invalid email or password.'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'] ?: 'owner';
    audit_log('login', 'admin', (int)$admin['id']);
    json_response(['ok' => true, 'email' => $admin['email'], 'role' => $_SESSION['admin_role'], 'csrf_token' => issue_csrf_token()]);
}

if ($action === 'logout') {
    require_once __DIR__ . '/../config/auth.php';
    require_csrf();
    session_destroy();
    json_response(['ok' => true]);
}

if ($action === 'dashboard') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    $pdo = db();
    json_response([
        'orders' => $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll(),
        'menu' => $pdo->query('SELECT * FROM menu_items ORDER BY id')->fetchAll(),
        'messages' => $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT 50')->fetchAll(),
        'subscribers' => $pdo->query('SELECT * FROM subscribers ORDER BY id DESC')->fetchAll(),
        'order_items' => $pdo->query('SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON mi.id = oi.menu_item_id ORDER BY oi.order_id DESC')->fetchAll(),
        'delivery_zones' => $pdo->query('SELECT * FROM delivery_zones ORDER BY name')->fetchAll(),
    ]);
}

if ($action === 'reports') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_method('GET');
    $days = filter_var($_GET['days'] ?? 30, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 366]]);
    if ($days === false) json_response(['error' => 'Days must be between 1 and 366.'], 422);
    $statement = db()->prepare(
        "SELECT substr(created_at, 1, 10) AS day, COUNT(*) AS orders,
                COALESCE(SUM(total), 0) AS revenue,
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN total ELSE 0 END), 0) AS paid_revenue
         FROM orders WHERE created_at >= datetime('now', ?)
         GROUP BY day ORDER BY day"
    );
    $statement->execute(['-' . $days . ' days']);
    json_response(['days' => $days, 'daily' => $statement->fetchAll()]);
}

if ($action === 'order-lookup') {
    require_once __DIR__ . '/../config/database.php';
    require_method('GET');
    enforce_rate_limit(db(), 'order-lookup', 20, 300);
    $phone = clean_phone($_GET['phone'] ?? '');
    $reference = strtoupper(trim((string)($_GET['reference'] ?? '')));
    $token = trim((string)($_GET['token'] ?? ''));
    if (!preg_match('/^MMB-[0-9]{6}-[A-F0-9]{8}$/', $reference) || !preg_match('/^[a-f0-9]{40}$/', $token)) {
        json_response(['error' => 'A valid order reference and token are required.'], 422);
    }

    $statement = db()->prepare('SELECT * FROM orders WHERE phone = ? AND order_reference = ?');
    $statement->execute([$phone, $reference]);
    $order = $statement->fetch();
    if (!$order || !password_verify($token, (string)$order['access_token_hash'])) {
        json_response(['error' => 'Order not found or credentials are invalid.'], 404);
    }
    $items = db()->prepare('SELECT oi.menu_item_id, oi.quantity, oi.unit_price, mi.name FROM order_items oi JOIN menu_items mi ON mi.id = oi.menu_item_id WHERE oi.order_id = ?');
    $items->execute([$order['id']]);
    unset($order['access_token_hash']);
    json_response(['order' => $order, 'items' => $items->fetchAll()]);
}

if ($action === 'payment-instruction') {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/providers.php';
    require_method('POST');
    $input = request_json();
    $reference = strtoupper(trim((string)($input['reference'] ?? '')));
    $provider = strtolower(trim((string)($input['provider'] ?? 'manual')));
    $phone = clean_phone($input['phone'] ?? '');
    if (!preg_match('/^MMB-[0-9]{6}-[A-F0-9]{8}$/', $reference)) {
        json_response(['error' => 'A valid order reference is required.'], 422);
    }
    if ($provider === 'manual') $provider = 'mobile_money';
    $registry = provider_registry();
    if (!isset($registry[$provider]) && $provider !== 'mobile_money') {
        json_response(['error' => 'Unsupported payment provider.'], 422);
    }
    $statement = db()->prepare('SELECT id,total,phone FROM orders WHERE order_reference = ?');
    $statement->execute([$reference]);
    $order = $statement->fetch();
    if (!$order || !hash_equals((string)$order['phone'], $phone)) json_response(['error' => 'Order not found.'], 404);
    $label = $registry[$provider]['name'] ?? 'mobile money';
    $instruction = payment_instruction($label);
    $transaction = db()->prepare('INSERT INTO payment_transactions (order_id,provider,method,status,amount,customer_phone,instruction) VALUES (?,?,?,?,?,?,?)');
    $transaction->execute([(int)$order['id'], $provider, 'manual', 'pending', (int)$order['total'], $phone, $instruction]);
    json_response(['ok' => true, 'transaction_id' => (int)db()->lastInsertId(), 'status' => 'pending', 'amount' => (int)$order['total'], 'currency' => 'TZS', 'instruction' => $instruction], 201);
}

if ($action === 'payment-transaction') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_method('GET');
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) json_response(['error' => 'A valid transaction is required.'], 422);
    $statement = db()->prepare('SELECT id,order_id,provider,method,status,amount,currency,customer_phone,provider_reference,instruction,created_at,updated_at FROM payment_transactions WHERE id = ?');
    $statement->execute([$id]);
    $transaction = $statement->fetch();
    if (!$transaction) json_response(['error' => 'Payment transaction not found.'], 404);
    json_response(['transaction' => $transaction]);
}

if ($action === 'order-detail') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_method('GET');
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) json_response(['error' => 'A valid order is required.'], 422);
    $statement = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $statement->execute([$id]);
    $order = $statement->fetch();
    if (!$order) json_response(['error' => 'Order not found.'], 404);
    unset($order['access_token_hash']);
    $items = db()->prepare('SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON mi.id = oi.menu_item_id WHERE oi.order_id = ?');
    $items->execute([$id]);
    json_response(['order' => $order, 'items' => $items->fetchAll()]);
}

if ($action === 'order-status') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('PATCH');
    $input = request_json();
    if (!in_array($input['status'] ?? '', ['Pending', 'Accepted', 'Ready', 'Delivered', 'Completed', 'Cancelled'], true)) {
        json_response(['error' => 'Invalid order status.'], 422);
    }
    $statement = db()->prepare('UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $statement->execute([$input['status'], (int)($input['id'] ?? 0)]);
    audit_log('order_status', 'order', (int)$input['id'], ['status' => $input['status']]);
    json_response(['ok' => true]);
}

if ($action === 'order-payment') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('PATCH');
    $input = request_json();
    if (!in_array($input['payment_status'] ?? '', ['Pending', 'Paid', 'Failed', 'Refunded'], true)) {
        json_response(['error' => 'Invalid payment status.'], 422);
    }
    $id = (int)($input['id'] ?? 0);
    $exists = db()->prepare('SELECT COUNT(*) FROM orders WHERE id = ?');
    $exists->execute([$id]);
    if ((int)$exists->fetchColumn() === 0) json_response(['error' => 'Order not found.'], 404);
    $statement = db()->prepare("UPDATE orders SET payment_status = ?, paid_at = CASE WHEN ? = 'Paid' THEN CURRENT_TIMESTAMP ELSE paid_at END, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $statement->execute([$input['payment_status'], $input['payment_status'], $id]);
    audit_log('order_payment', 'order', $id, ['payment_status' => $input['payment_status']]);
    json_response(['ok' => true]);
}

if ($action === 'order-preparation') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('PATCH');
    $input = request_json();
    if (!in_array($input['status'] ?? '', ['Accepted', 'Ready'], true)) {
        json_response(['error' => 'Preparation status must be Accepted or Ready.'], 422);
    }
    $id = (int)($input['id'] ?? 0);
    $exists = db()->prepare('SELECT COUNT(*) FROM orders WHERE id = ?');
    $exists->execute([$id]);
    if ((int)$exists->fetchColumn() === 0) json_response(['error' => 'Order not found.'], 404);
    $statement = db()->prepare('UPDATE orders SET status = ?, prepared_at = CASE WHEN ? = "Ready" THEN CURRENT_TIMESTAMP ELSE prepared_at END, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
    $statement->execute([$input['status'], $input['status'], $id]);
    audit_log('order_preparation', 'order', $id, ['status' => $input['status']]);
    json_response(['ok' => true]);
}

if ($action === 'delivery-zone-save') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('POST');
    $input = request_json();
    $name = clean_text($input['name'] ?? '', 100);
    $fee = filter_var($input['fee'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($name === '' || $fee === false) {
        json_response(['error' => 'A zone name and non-negative fee are required.'], 422);
    }
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    try {
        if ($id !== false && $id !== null) {
            $exists = db()->prepare('SELECT COUNT(*) FROM delivery_zones WHERE id = ?');
            $exists->execute([$id]);
            if ((int)$exists->fetchColumn() === 0) json_response(['error' => 'Delivery zone not found.'], 404);
            $active = array_key_exists('active', $input) ? (!empty($input['active']) ? 1 : 0) : 1;
            $statement = db()->prepare('UPDATE delivery_zones SET name = ?, fee = ?, active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $statement->execute([$name, $fee, $active, $id]);
            audit_log('delivery_zone_save', 'delivery_zone', $id, ['name' => $name, 'fee' => $fee, 'active' => $active]);
            json_response(['ok' => true, 'id' => $id]);
        }
        $active = array_key_exists('active', $input) ? (!empty($input['active']) ? 1 : 0) : 1;
        $statement = db()->prepare('INSERT INTO delivery_zones (name,fee,active) VALUES (?,?,?)');
        $statement->execute([$name, $fee, $active]);
        $newId = (int)db()->lastInsertId();
        audit_log('delivery_zone_save', 'delivery_zone', $newId, ['name' => $name, 'fee' => $fee, 'active' => $active]);
        json_response(['ok' => true, 'id' => $newId], 201);
    } catch (PDOException $error) {
        if ((int)$error->errorInfo[1] === 19) json_response(['error' => 'A delivery zone with that name already exists.'], 409);
        throw $error;
    }
}

if ($action === 'delivery-zone-delete') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('DELETE');
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) json_response(['error' => 'A valid delivery zone is required.'], 422);
    $used = db()->prepare('SELECT COUNT(*) FROM orders WHERE delivery_zone_id = ?');
    $used->execute([$id]);
    if ((int)$used->fetchColumn() > 0) json_response(['error' => 'This zone is used by an existing order; deactivate it instead.'], 409);
    $statement = db()->prepare('DELETE FROM delivery_zones WHERE id = ?');
    $statement->execute([$id]);
    if ($statement->rowCount() === 0) json_response(['error' => 'Delivery zone not found.'], 404);
    audit_log('delivery_zone_delete', 'delivery_zone', $id);
    json_response(['ok' => true]);
}

if ($action === 'menu-save') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('POST');
    $input = request_json();
    $name = clean_text($input['name'] ?? '', 100);
    $category = clean_text($input['category'] ?? '', 80);
    $price = filter_var($input['price'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($name === '' || $category === '' || $price === false) {
        json_response(['error' => 'Name, category and a positive price are required.'], 422);
    }
    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false && $id !== null) {
        $exists = db()->prepare('SELECT COUNT(*) FROM menu_items WHERE id = ?');
        $exists->execute([$id]);
        if ((int)$exists->fetchColumn() === 0) {
            json_response(['error' => 'Menu item not found.'], 404);
        }
        $statement = db()->prepare('UPDATE menu_items SET name = ?, category = ?, price = ? WHERE id = ?');
        $statement->execute([$name, $category, $price, $id]);
        audit_log('menu_save', 'menu_item', $id, ['name' => $name, 'category' => $category, 'price' => $price]);
        json_response(['ok' => true, 'id' => $id]);
    }
    $statement = db()->prepare('INSERT INTO menu_items (name,category,price,available) VALUES (?,?,?,1)');
    $statement->execute([$name, $category, $price]);
    $newId = (int)db()->lastInsertId();
    audit_log('menu_save', 'menu_item', $newId, ['name' => $name, 'category' => $category, 'price' => $price]);
    json_response(['ok' => true, 'id' => $newId], 201);
}

if ($action === 'menu-delete') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('DELETE');
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        json_response(['error' => 'A valid menu item is required.'], 422);
    }
    $used = db()->prepare('SELECT COUNT(*) FROM order_items WHERE menu_item_id = ?');
    $used->execute([$id]);
    if ((int)$used->fetchColumn() > 0) {
        json_response(['error' => 'This item is part of an existing order. Hide it instead of deleting it.'], 409);
    }
    $statement = db()->prepare('DELETE FROM menu_items WHERE id = ?');
    $statement->execute([$id]);
    if ($statement->rowCount() === 0) {
        json_response(['error' => 'Menu item not found.'], 404);
    }
    audit_log('menu_delete', 'menu_item', $id);
    json_response(['ok' => true]);
}

if ($action === 'menu-availability') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('PATCH');
    $input = request_json();
    $statement = db()->prepare('UPDATE menu_items SET available = CASE WHEN available = 1 THEN 0 ELSE 1 END WHERE id = ?');
    $statement->execute([(int)($input['id'] ?? 0)]);
    audit_log('menu_availability', 'menu_item', (int)$input['id']);
    json_response(['ok' => true]);
}

if ($action === 'message-read') {
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
    require_csrf();
    require_method('PATCH');
    $statement = db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?');
    $statement->execute([(int)($_GET['id'] ?? 0)]);
    json_response(['ok' => true]);
}

json_response(['error' => 'Unknown API action.'], 404);
