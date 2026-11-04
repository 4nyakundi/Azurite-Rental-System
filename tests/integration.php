<?php
// Uses a disposable database. Never imports the demo reset into an existing database.
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/booking_service.php';
$configFile = __DIR__ . '/../includes/config.local.php';
$config = is_file($configFile) ? require $configFile : [];
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: ($config['host'] ?? 'localhost')) . ';charset=utf8mb4', getenv('DB_USER') ?: ($config['user'] ?? 'root'), getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($config['password'] ?? ''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$database = 'azurite_test_' . bin2hex(random_bytes(5));
if (($argv[1] ?? '') === '--cleanup') {
    $database = $argv[2] ?? '';
    if (!preg_match('/^azurite_test_[a-f0-9]{10}$/', $database)) exit(1);
    $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
    exit;
}
$keep = ($argv[1] ?? '') === '--keep';
$checks = 0;
$exitCode = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function rejected(callable $action, $message) {
    try { $action(); } catch (DomainException $exception) { check(true, $message); return; }
    throw new RuntimeException($message);
}
try {
    $schema = str_replace('azurite_rental', $database, file_get_contents(__DIR__ . '/../database/database.sql'));
    $pdo->exec($schema);
    // Verify that the demo reset can be imported twice into its own database.
    $pdo->exec($schema);
    $pdo->exec(file_get_contents(__DIR__ . '/../database/upgrade.sql'));
    check(verify_password('admin123', md5('admin123')), 'Legacy passwords must remain usable.');
    check(verify_password('long-password', password_hash('long-password', PASSWORD_DEFAULT)), 'Modern password verification.');
    check(!verify_password('wrong', md5('admin123')), 'Reject wrong password.');
    check(valid_date('2028-02-29') && !valid_date('2027-02-29') && !valid_date('tomorrow'), 'Strict calendar validation.');
    check(e(null) === '' && e('<script>') === '&lt;script&gt;', 'HTML escaping.');
    $_SERVER['SCRIPT_NAME'] = '/custom-folder/public/host/index.php';
    check(public_base() === '/custom-folder/public/', 'Deployment folder detection.');
    $tenant = $pdo->query("SELECT * FROM users WHERE username='user'")->fetch();
    $host = $pdo->query("SELECT * FROM users WHERE username='host'")->fetch();
    $admin = $pdo->query("SELECT * FROM users WHERE username='admin'")->fetch();
    $cin = (new DateTimeImmutable('today'))->modify('+20 days')->format('Y-m-d');
    $cout = (new DateTimeImmutable($cin))->modify('+2 days')->format('Y-m-d');
    $end = (new DateTimeImmutable($cout))->modify('+2 days')->format('Y-m-d');
    $input = ['room_id'=>1,'checkin'=>$cin,'checkout'=>$cout,'guests'=>2,'contact_phone'=>'0712345678'];
    $ref = create_booking($pdo, $input, $tenant);
    $stmt=$pdo->prepare('SELECT * FROM bookings WHERE ref_code=?'); $stmt->execute([$ref]); $booking=$stmt->fetch();
    check($booking['status']==='Pending' && (float)$booking['total_amount_ksh']===13000.0, 'Server-calculated price and pending status.');
    rejected(fn()=>create_booking($pdo, $input, $tenant), 'Reject overlapping bookings.');
    $adjacent = array_merge($input, ['checkin'=>$cout,'checkout'=>$end]);
    create_booking($pdo, $adjacent, $tenant);
    check(true, 'Allow adjacent checkout/checkin.');
    $pdo->prepare('INSERT INTO room_block_dates (room_id,block_date) VALUES (?,?)')->execute([2,$cin]);
    rejected(fn()=>create_booking($pdo, array_merge($input,['room_id'=>2]), $tenant), 'Reject host-blocked nights.');
    foreach ([['checkin'=>'bad-date'],['checkin'=>'2020-01-01'],['checkout'=>$cin],['guests'=>0],['guests'=>5],['contact_phone'=>'abc'],['room_id'=>9999]] as $invalid) {
        rejected(fn()=>create_booking($pdo,array_merge($input,$invalid),$tenant), 'Reject invalid booking input.');
    }
    rejected(fn()=>create_booking($pdo,$input,$host), 'Only tenants may book.');
    check(!$pdo->inTransaction(), 'Rejected bookings release transactions.');
    $stranger=['id'=>9999,'role'=>'host'];
    rejected(fn()=>update_booking_status($pdo,$booking['id'],'Approved',$stranger), 'Enforce host ownership.');
    update_booking_status($pdo,$booking['id'],'Approved',$host);
    check((int)$pdo->query('SELECT COUNT(*) FROM booking_events')->fetchColumn()===1, 'Write status audit atomically.');
    update_booking_status($pdo,$booking['id'],'Cancelled',$host);
    create_booking($pdo,$input,$tenant);
    rejected(fn()=>update_booking_status($pdo,$booking['id'],'Confirmed',$admin), 'Reject conflicting reactivation.');
    check(!$pdo->inTransaction(), 'Rejected status changes release transactions.');
    // Inactive account fixture for HTTP authentication checks.
    $pdo->exec("INSERT INTO users (full_name,username,email,password_md5,role,is_active) VALUES ('Disabled','disabled','disabled@example.test',MD5('password123'),'tenant',0)");
    echo json_encode(['database'=>$database,'checks'=>$checks], JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $exception) {
    $keep = false;
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (!$keep) $pdo->exec('DROP DATABASE IF EXISTS `' . $database . '`');
}
exit($exitCode);
