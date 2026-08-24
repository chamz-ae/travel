<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/security.php';
require_once CONFIG_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';

Auth::requireAuth();
$db = Database::getConnection();

$statusFilter = Security::sanitizeString($_GET['status'] ?? '');
$startDate    = Security::sanitizeString($_GET['start_date'] ?? '');
$endDate      = Security::sanitizeString($_GET['end_date'] ?? '');

$sql = "
    SELECT r.booking_code, r.customer_name, r.customer_phone, r.customer_email,
           r.pickup_date, r.pickup_time, r.duration_days, r.passengers,
           r.pickup_location, r.dropoff_location, r.special_requests,
           r.status, r.language_used, r.created_at,
           COALESCE(st.title, s.identifier, 'Custom Trip') AS service_name
    FROM reservations r
    LEFT JOIN services s ON r.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_code = 'id'
    WHERE 1=1
";

if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $sql .= " AND r.status = '{$statusFilter}'";
}
if (!empty($startDate)) {
    $sql .= " AND r.pickup_date >= '{$startDate}'";
}
if (!empty($endDate)) {
    $sql .= " AND r.pickup_date <= '{$endDate}'";
}

$sql .= " ORDER BY r.pickup_date ASC, r.pickup_time ASC";
$result = $db->query($sql);

$filename = 'reservasi_tiranda_' . date('Ymd_His') . '.csv';

// Output HTTP Headers untuk Download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM untuk Microsoft Excel
fputs($output, "\xEF\xBB\xBF");

// Header Kolom CSV
fputcsv($output, [
    'Kode Booking',
    'Nama Pelanggan',
    'No. WhatsApp / Telepon',
    'Email',
    'Layanan',
    'Tanggal Jemput',
    'Waktu',
    'Durasi (Hari)',
    'Pax',
    'Lokasi Penjemputan',
    'Lokasi Tujuan / Drop',
    'Catatan Khusus',
    'Status',
    'Bahasa',
    'Tanggal Pemesanan'
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['booking_code'],
        $row['customer_name'],
        $row['customer_phone'],
        $row['customer_email'],
        $row['service_name'],
        $row['pickup_date'],
        $row['pickup_time'],
        $row['duration_days'],
        $row['passengers'],
        $row['pickup_location'],
        $row['dropoff_location'],
        $row['special_requests'],
        strtoupper($row['status']),
        strtoupper($row['language_used']),
        $row['created_at']
    ]);
}

fclose($output);
exit;