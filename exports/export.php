<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

try {
    $jadwal = $pdo->query("SELECT j.*, m.nama as nama_mk, k.nama as nama_kelas, r.nama as nama_ruangan, s.hari, s.jam, d.nama as nama_dosen 
                           FROM jadwal j 
                           JOIN mata_kuliah m ON j.id_mk = m.id_mk 
                           JOIN kelas k ON j.id_kelas = k.id_kelas 
                           JOIN ruangan r ON j.id_ruangan = r.id_ruangan 
                           JOIN slot_waktu s ON j.id_slot = s.id_slot 
                           JOIN dosen d ON j.id_dosen = d.id_dosen")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=jadwal_kuliah.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Semester', 'Mata Kuliah', 'Kelas', 'Ruangan', 'Hari', 'Jam', 'Dosen']);

    foreach ($jadwal as $j) {
        fputcsv($output, [
            $j['semester'],
            $j['nama_mk'],
            $j['nama_kelas'],
            $j['nama_ruangan'],
            $j['hari'],
            $j['jam'],
            $j['nama_dosen']
        ]);
    }

    fclose($output);
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . htmlspecialchars($e->getMessage());
    header('Location: jadwal.php');
    exit;
}
?>