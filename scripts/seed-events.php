<?php

// Seed realistic analytics events and orders across 4 cuisines in INR
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/init_db.php';
require_once __DIR__ . '/../backend/server_sdk.php';

echo "Seeding realistic analytics events and orders across 4 cuisines into MySQL...\n";

init_database();
$conn = get_db_connection();

$dishes = [
    // Indian
    ['id' => 'ind-1', 'name' => 'Old Delhi Butter Chicken', 'price' => 380, 'cat' => 'indian'],
    ['id' => 'ind-2', 'name' => 'Paneer Tikka Angara', 'price' => 310, 'cat' => 'indian'],
    ['id' => 'ind-3', 'name' => 'Hyderabadi Dum Biryani', 'price' => 360, 'cat' => 'indian'],
    ['id' => 'ind-4', 'name' => 'Dal Makhani Bukhara', 'price' => 260, 'cat' => 'indian'],
    // Mexican
    ['id' => 'mex-1', 'name' => 'Birria Quesatacos (3 pcs)', 'price' => 340, 'cat' => 'mexican'],
    ['id' => 'mex-2', 'name' => 'Loaded Guacamole Nachos', 'price' => 290, 'cat' => 'mexican'],
    ['id' => 'mex-3', 'name' => 'Sizzling Chipotle Fajitas', 'price' => 380, 'cat' => 'mexican'],
    ['id' => 'mex-4', 'name' => 'Cinnamon Churros & Dulce', 'price' => 220, 'cat' => 'mexican'],
    // Italian
    ['id' => 'ita-1', 'name' => 'Pizza Margherita Verace DOC', 'price' => 360, 'cat' => 'italian'],
    ['id' => 'ita-2', 'name' => 'Truffle Tagliolini al Parmigiano', 'price' => 450, 'cat' => 'italian'],
    ['id' => 'ita-3', 'name' => 'Spaghetti alla Carbonara Autentica', 'price' => 390, 'cat' => 'italian'],
    ['id' => 'ita-4', 'name' => 'Tiramisù Tradizionale al Mascarpone', 'price' => 240, 'cat' => 'italian'],
    // Chinese
    ['id' => 'chi-1', 'name' => 'Dim Sum Crystal Dumplings (6 pcs)', 'price' => 320, 'cat' => 'chinese'],
    ['id' => 'chi-2', 'name' => 'Schezwan Hakka Tossed Noodles', 'price' => 270, 'cat' => 'chinese'],
    ['id' => 'chi-3', 'name' => 'Kung Pao Crispy Chicken', 'price' => 350, 'cat' => 'chinese'],
    ['id' => 'chi-4', 'name' => 'Crispy Golden Spring Rolls', 'price' => 210, 'cat' => 'chinese'],
];

$pages = [
    ['path' => '/menu/indian', 'title' => 'Taste of Punjab & Awadh'],
    ['path' => '/menu/mexican', 'title' => 'Taqueria & Cantina Favourites'],
    ['path' => '/menu/italian', 'title' => 'Artisan Pizza & Handmade Pasta'],
    ['path' => '/menu/chinese', 'title' => 'Wok Specialties & Dim Sum'],
    ['path' => '/about', 'title' => 'Our Heritage & 4 Cuisines'],
    ['path' => '/reservations', 'title' => 'Reserve a Dining Table'],
];

$referrers = [
    'https://www.google.com/',
    'https://www.instagram.com/',
    'https://www.zomato.com/',
    'https://www.swiggy.com/',
    '',
];

$userAgents = [
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
];

$eventStmt = mysqli_prepare($conn, "
    INSERT IGNORE INTO events (
        event_id, event_name, timestamp, timestamp_epoch, source,
        anonymous_id, session_id, user_id, properties, context, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");

$orderStmt = mysqli_prepare($conn, "
    INSERT IGNORE INTO orders (
        order_id, customer_name, customer_email, table_or_delivery,
        total_amount, currency, items, status, anonymous_id, session_id, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, NOW())
");

$now = time();
$seededEvents = 0;
$seededOrders = 0;

for ($u = 1; $u <= 40; $u++) {
    $anonId = 'anon_' . bin2hex(random_bytes(6));
    $sessionId = 'sess_' . bin2hex(random_bytes(6));
    $userId = $u <= 12 ? 'user_' . $u : null;
    $dayOffset = mt_rand(0, 5); // 0 to 5 days ago
    $hourOffset = mt_rand(8, 22);
    $minuteOffset = mt_rand(0, 59);
    $baseTime = $now - ($dayOffset * 86400) + (($hourOffset - 12) * 3600) + ($minuteOffset * 60);

    $ua = $userAgents[array_rand($userAgents)];
    $ref = $referrers[array_rand($referrers)];

    // 1. page_view
    $p = $pages[array_rand($pages)];
    $t1 = gmdate('Y-m-d\TH:i:s.v\Z', $baseTime);
    $ep1 = $baseTime * 1000;
    $srcBrowser = 'browser';
    $p1Prop = json_encode(['path' => $p['path'], 'title' => $p['title']]);
    $p1Ctx = json_encode([
        'url' => 'http://localhost:5173' . $p['path'],
        'path' => $p['path'],
        'title' => $p['title'],
        'referrer' => $ref,
        'user_agent' => $ua,
        'screen_resolution' => '1920x1080',
        'sdk_name' => 'analytics-browser-sdk',
        'sdk_version' => '1.0.0',
    ]);
    $eId1 = generate_uuid_v4();

    $pName = 'page_view';
    mysqli_stmt_bind_param($eventStmt, 'sssissssss', $eId1, $pName, $t1, $ep1, $srcBrowser, $anonId, $sessionId, $userId, $p1Prop, $p1Ctx);
    mysqli_stmt_execute($eventStmt);
    $seededEvents++;

    // 2. view_item (75%)
    if (mt_rand(1, 100) <= 75) {
        $dish = $dishes[array_rand($dishes)];
        $t2 = gmdate('Y-m-d\TH:i:s.v\Z', $baseTime + 30);
        $ep2 = ($baseTime + 30) * 1000;
        $vName = 'view_item';
        $vProp = json_encode(['item_id' => $dish['id'], 'name' => $dish['name'], 'price' => $dish['price'], 'category' => $dish['cat']]);
        $vCtx = json_encode(['user_agent' => $ua]);
        $eId2 = generate_uuid_v4();

        mysqli_stmt_bind_param($eventStmt, 'sssissssss', $eId2, $vName, $t2, $ep2, $srcBrowser, $anonId, $sessionId, $userId, $vProp, $vCtx);
        mysqli_stmt_execute($eventStmt);
        $seededEvents++;

        // 3. add_to_cart (60%)
        if (mt_rand(1, 100) <= 60) {
            $t3 = gmdate('Y-m-d\TH:i:s.v\Z', $baseTime + 75);
            $ep3 = ($baseTime + 75) * 1000;
            $aName = 'add_to_cart';
            $aProp = json_encode(['item_id' => $dish['id'], 'name' => $dish['name'], 'price' => $dish['price'], 'quantity' => 1]);
            $aCtx = json_encode(['user_agent' => $ua]);
            $eId3 = generate_uuid_v4();

            mysqli_stmt_bind_param($eventStmt, 'sssissssss', $eId3, $aName, $t3, $ep3, $srcBrowser, $anonId, $sessionId, $userId, $aProp, $aCtx);
            mysqli_stmt_execute($eventStmt);
            $seededEvents++;

            // 4. checkout_started (50%)
            if (mt_rand(1, 100) <= 50) {
                $tax = round($dish['price'] * 0.05, 2); // 5% GST
                $totalAmt = round($dish['price'] + $tax, 2);
                $t4 = gmdate('Y-m-d\TH:i:s.v\Z', $baseTime + 130);
                $ep4 = ($baseTime + 130) * 1000;
                $cName = 'checkout_started';
                $cProp = json_encode(['total_amount' => $totalAmt, 'items_count' => 1, 'currency' => 'INR']);
                $cCtx = json_encode(['user_agent' => $ua]);
                $eId4 = generate_uuid_v4();

                mysqli_stmt_bind_param($eventStmt, 'sssissssss', $eId4, $cName, $t4, $ep4, $srcBrowser, $anonId, $sessionId, $userId, $cProp, $cCtx);
                mysqli_stmt_execute($eventStmt);
                $seededEvents++;

                // 5. order_created (70% - Server-Side Event!)
                if (mt_rand(1, 100) <= 70) {
                    $orderId = 'ORD-' . strtoupper(substr(uniqid(), -6));
                    $t5 = gmdate('Y-m-d\TH:i:s.v\Z', $baseTime + 180);
                    $ep5 = ($baseTime + 180) * 1000;
                    $srcServer = 'server';
                    $oName = 'order_created';
                    $custNames = ['Rohan Verma', 'Ananya Iyer', 'Vikram Malhotra', 'Sneha Kapoor', 'Karan Patel', 'Meera Rao'];
                    $custName = $custNames[array_rand($custNames)];
                    $custEmail = strtolower(str_replace(' ', '.', $custName)) . '@example.com';
                    $table = 'Table ' . mt_rand(1, 12);
                    $currency = 'INR';

                    $oProp = json_encode([
                        'order_id' => $orderId,
                        'customer_name' => $custName,
                        'customer_email' => $custEmail,
                        'table_or_delivery' => $table,
                        'total_amount' => $totalAmt,
                        'currency' => $currency,
                        'items_count' => 1,
                        'items' => [$dish],
                    ]);
                    $oCtx = json_encode([
                        'sdk_name' => 'analytics-laravel-sdk',
                        'sdk_version' => '1.0.0',
                        'runtime' => 'PHP ' . PHP_VERSION,
                    ]);
                    $eId5 = generate_uuid_v4();

                    mysqli_stmt_bind_param($eventStmt, 'sssissssss', $eId5, $oName, $t5, $ep5, $srcServer, $anonId, $sessionId, $userId, $oProp, $oCtx);
                    mysqli_stmt_execute($eventStmt);
                    $seededEvents++;

                    $createdAtSql = date('Y-m-d H:i:s', $baseTime + 180);
                    $itemsJson = json_encode([$dish]);
                    mysqli_stmt_bind_param($orderStmt, 'ssssdsssss', $orderId, $custName, $custEmail, $table, $totalAmt, $currency, $itemsJson, $anonId, $sessionId, $createdAtSql);
                    mysqli_stmt_execute($orderStmt);
                    $seededOrders++;
                }
            }
        }
    }
}

mysqli_stmt_close($eventStmt);
mysqli_stmt_close($orderStmt);

echo "Successfully seeded {$seededEvents} telemetry events and {$seededOrders} restaurant orders in INR into MySQL!\n";
