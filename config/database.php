<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (!extension_loaded('pdo_sqlite')) {
        json_response(['error' => 'The server requires the PHP pdo_sqlite extension.'], 503);
    }
    $directory = dirname(DB_PATH);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        json_response(['error' => 'Unable to create the database directory.'], 500);
    }
    try {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        migrate($pdo);
        return $pdo;
    } catch (Throwable $error) {
        error_log($error->getMessage());
        json_response(['error' => 'The database is unavailable.'], 500);
    }
}

function migrate(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "owner",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS menu_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            price INTEGER NOT NULL,
            available INTEGER NOT NULL DEFAULT 1,
            image_path TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            subject TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS subscribers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            order_type TEXT NOT NULL,
            address TEXT,
            notes TEXT,
            subtotal INTEGER NOT NULL DEFAULT 0,
            delivery_fee INTEGER NOT NULL DEFAULT 0,
            payment_method TEXT NOT NULL DEFAULT \'Cash\',
            payment_status TEXT NOT NULL DEFAULT \'Pending\',
            delivery_zone_id INTEGER,
            total INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT "Pending",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT,
            prepared_at TEXT,
            paid_at TEXT,
            order_reference TEXT UNIQUE,
            access_token_hash TEXT,
            FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zones(id)
        );
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
            quantity INTEGER NOT NULL,
            unit_price INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS delivery_zones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            fee INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT
        );
        CREATE TABLE IF NOT EXISTS api_rate_limits (
            rate_key TEXT PRIMARY KEY,
            window_started INTEGER NOT NULL,
            request_count INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            action TEXT NOT NULL,
            entity_type TEXT,
            entity_id INTEGER,
            details TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
        );
        CREATE TABLE IF NOT EXISTS payment_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            provider TEXT NOT NULL,
            method TEXT NOT NULL DEFAULT \'manual\',
            status TEXT NOT NULL DEFAULT \'pending\',
            amount INTEGER NOT NULL,
            currency TEXT NOT NULL DEFAULT \'TZS\',
            customer_phone TEXT,
            provider_reference TEXT,
            instruction TEXT,
            metadata TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT,
            CHECK (status IN (\'pending\',\'submitted\',\'processing\',\'succeeded\',\'failed\',\'cancelled\',\'expired\')),
            CHECK (amount >= 0)
        );
        CREATE TABLE IF NOT EXISTS notification_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            channel TEXT NOT NULL DEFAULT \'sms\',
            event_type TEXT NOT NULL,
            recipient TEXT NOT NULL,
            payload TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT \'queued\',
            available_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT,
            CHECK (channel IN (\'sms\')),
            CHECK (status IN (\'queued\',\'processing\',\'sent\',\'failed\',\'cancelled\'))
        );
        CREATE TABLE IF NOT EXISTS notification_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            notification_job_id INTEGER NOT NULL REFERENCES notification_jobs(id) ON DELETE CASCADE,
            provider TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT \'pending\',
            response_code INTEGER,
            error_message TEXT,
            attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CHECK (status IN (\'pending\',\'sent\',\'failed\'))
        );'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_payment_transactions_order ON payment_transactions(order_id);
        CREATE INDEX IF NOT EXISTS idx_payment_transactions_status ON payment_transactions(status);
        CREATE INDEX IF NOT EXISTS idx_notification_jobs_status ON notification_jobs(status);
        CREATE INDEX IF NOT EXISTS idx_notification_attempts_job ON notification_attempts(notification_job_id);');
    $adminColumns = [];
    foreach ($pdo->query('PRAGMA table_info(admins)') as $column) {
        $adminColumns[$column['name']] = true;
    }
    if (!isset($adminColumns['role'])) {
        $pdo->exec('ALTER TABLE admins ADD COLUMN role TEXT NOT NULL DEFAULT "owner"');
    }
    $pdo->exec("UPDATE admins SET role = 'owner' WHERE role IS NULL OR role = ''");
    // ALTER TABLE is used only for columns missing from databases created by
    // earlier versions. No existing data is removed.
    $columns = [
        'subtotal' => "ALTER TABLE orders ADD COLUMN subtotal INTEGER NOT NULL DEFAULT 0",
        'delivery_fee' => "ALTER TABLE orders ADD COLUMN delivery_fee INTEGER NOT NULL DEFAULT 0",
        'payment_method' => "ALTER TABLE orders ADD COLUMN payment_method TEXT NOT NULL DEFAULT 'Cash'",
        'payment_status' => "ALTER TABLE orders ADD COLUMN payment_status TEXT NOT NULL DEFAULT 'Pending'",
        'delivery_zone_id' => 'ALTER TABLE orders ADD COLUMN delivery_zone_id INTEGER',
        'updated_at' => 'ALTER TABLE orders ADD COLUMN updated_at TEXT',
        'prepared_at' => 'ALTER TABLE orders ADD COLUMN prepared_at TEXT',
        'paid_at' => 'ALTER TABLE orders ADD COLUMN paid_at TEXT',
        'order_reference' => 'ALTER TABLE orders ADD COLUMN order_reference TEXT',
        'access_token_hash' => 'ALTER TABLE orders ADD COLUMN access_token_hash TEXT',
    ];
    $menuColumns = [];
    foreach ($pdo->query('PRAGMA table_info(menu_items)') as $column) {
        $menuColumns[$column['name']] = true;
    }
    if (!isset($menuColumns['image_path'])) {
        $pdo->exec('ALTER TABLE menu_items ADD COLUMN image_path TEXT');
    }
    $pdo->exec("UPDATE menu_items
        SET image_path = 'img/menu-' || (((id - 1) % 8) + 1) || '.jpg'
        WHERE image_path IS NULL OR image_path = ''");
    $existing = [];
    foreach ($pdo->query('PRAGMA table_info(orders)') as $column) {
        $existing[$column['name']] = true;
    }
    foreach ($columns as $name => $sql) {
        if (!isset($existing[$name])) {
            $pdo->exec($sql);
        }
        // Backfill lookup credentials for orders created by older versions.
        $legacyOrders = $pdo->query('SELECT id FROM orders WHERE order_reference IS NULL OR access_token_hash IS NULL')->fetchAll(PDO::FETCH_COLUMN);
        $backfill = $pdo->prepare('UPDATE orders SET order_reference = ?, access_token_hash = ? WHERE id = ?');
        foreach ($legacyOrders as $legacyId) {
            $backfill->execute(['MMB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4))), password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), $legacyId]);
        }
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM delivery_zones')->fetchColumn() === 0) {
        $statement = $pdo->prepare('INSERT INTO delivery_zones (name, fee) VALUES (?, ?)');
        foreach ([['Mafinga mjini', 5000], ['Mafinga outskirts', 10000]] as $zone) {
            $statement->execute($zone);
        }
    }
    $pdo->exec("UPDATE delivery_zones SET name = 'Mafinga mjini' WHERE name = 'Tanga mjini'");
    $pdo->exec("UPDATE delivery_zones SET name = 'Mafinga outskirts' WHERE name = 'Tanga outskirts'");
    $pdo->exec('UPDATE orders SET subtotal = total WHERE subtotal = 0 AND delivery_fee = 0');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count === 0) {
        $adminPassword = getenv('MAMBO_ADMIN_PASSWORD');
        if ($adminPassword === false || trim($adminPassword) === '') {
            throw new RuntimeException('MAMBO_ADMIN_PASSWORD must be set before first database initialization.');
        }
        $statement = $pdo->prepare('INSERT INTO admins (email, password_hash, role) VALUES (?, ?, ?)');
        $statement->execute([
            getenv('MAMBO_ADMIN_EMAIL') ?: 'admin@mamboyatanga.co.tz',
            password_hash($adminPassword, PASSWORD_DEFAULT),
            'owner',
        ]);
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() === 0) {
        $statement = $pdo->prepare('INSERT INTO menu_items (name,category,price,available) VALUES (?,?,?,1)');
        foreach ([['Mambo Breakfast','Meals',18000],['Iringa Beef Fillet','Meals',32000],['Vegetable Curry','Meals',22000],['Fresh Fruit Platter','Dessert',12000],['Fresh Passion Juice','Juice',8000],['Family Rice Pan','Pans & Catering',65000],['Chicken Pilau Pan','Pans & Catering',85000]] as $item) {
            $statement->execute($item);
        }
    }
    $pdo->exec("UPDATE menu_items SET category = 'Desserts' WHERE category = 'Dessert';
        UPDATE menu_items SET category = 'Drinks' WHERE category = 'drinks';
        UPDATE menu_items SET category = 'Snacks' WHERE category = 'foods'");
    seedExpandedMenu($pdo);
}

function seedExpandedMenu(PDO $pdo): void
{
    $catalog = [
        ['Meals', 'Grilled Chicken with Chips', 18000],
        ['Meals', 'Beef Stew with Rice', 16000],
        ['Meals', 'Fish Fillet with Ugali', 22000],
        ['Meals', 'Chicken Biryani', 20000],
        ['Meals', 'Vegetable Rice Bowl', 14000],
        ['Juice', 'Fresh Mango Juice', 7000],
        ['Juice', 'Fresh Pineapple Juice', 7000],
        ['Juice', 'Fresh Watermelon Juice', 6000],
        ['Juice', 'Fresh Avocado Smoothie', 9000],
        ['Juice', 'Passion and Ginger Juice', 8000],
        ['Drinks', 'Masala Tea', 4000],
        ['Drinks', 'African Coffee', 5000],
        ['Drinks', 'Bottled Water', 2000],
        ['Drinks', 'Soda', 2500],
        ['Drinks', 'Iced Lemon Tea', 5000],
        ['Snacks', 'Beef Samosa (3 pieces)', 6000],
        ['Snacks', 'Chicken Spring Rolls (3 pieces)', 7000],
        ['Snacks', 'Chapati with Beans', 8000],
        ['Snacks', 'Mandazi with Tea', 5000],
        ['Snacks', 'French Fries', 7000],
        ['Desserts', 'Chocolate Cake Slice', 8000],
        ['Desserts', 'Vanilla Ice Cream', 7000],
        ['Desserts', 'Banana Fritters', 6000],
        ['Desserts', 'Fruit and Yoghurt Cup', 9000],
        ['Desserts', 'Mango Cheesecake Slice', 10000],
        ['Catering', 'Executive Lunch for 5', 95000],
        ['Catering', 'Family Chicken Feast', 110000],
        ['Catering', 'Beef Buffet for 10', 180000],
        ['Catering', 'Vegetarian Buffet for 10', 140000],
        ['Catering', 'Tea Break Package for 10', 85000],
        ['Pans & Catering', 'Beef Pilau Pan', 95000],
        ['Pans & Catering', 'Vegetarian Biryani Pan', 75000],
        ['Pans & Catering', 'Breakfast Catering Pan', 70000],
        ['Services', 'Event Buffet Setup', 50000],
        ['Services', 'Waiter Service for 3 Hours', 60000],
        ['Services', 'Office Lunch Delivery Package', 30000],
        ['Services', 'Disposable Catering Pack (10)', 15000],
        ['Services', 'Special Event Table Setup', 75000],
    ];
    $exists = $pdo->prepare('SELECT 1 FROM menu_items WHERE name = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO menu_items (name, category, price, available) VALUES (?, ?, ?, 1)');
    foreach ($catalog as [$category, $name, $price]) {
        $exists->execute([$name]);
        if ($exists->fetchColumn() === false) {
            $insert->execute([$name, $category, $price]);
        }
    }
}
