<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester = $_POST['semester'];
    $tanggal_pembuatan = date('Y-m-d');

    try {
        // Clear existing jadwal
        $pdo->exec("DELETE FROM jadwal");

        // Fetch data
        $mata_kuliah = $pdo->query("SELECT m.*, k.jumlah_mahasiswa, k.id_kelas FROM mata_kuliah m JOIN kelas k ON m.id_prodi = k.id_prodi")->fetchAll(PDO::FETCH_ASSOC);
        $ruangan = $pdo->query("SELECT * FROM ruangan")->fetchAll(PDO::FETCH_ASSOC);
        $slot_waktu = $pdo->query("SELECT * FROM slot_waktu")->fetchAll(PDO::FETCH_ASSOC);

        // Randomized Backtracking
        $jadwal = [];
        $id_jadwal = 1;

        foreach ($mata_kuliah as $mk) {
            shuffle($ruangan);
            shuffle($slot_waktu);

            $assigned = false;
            foreach ($slot_waktu as $slot) {
                foreach ($ruangan as $r) {
                    // Validate capacity
                    if ($mk['jumlah_mahasiswa'] > $r['kapasitas']) continue;

                    // Validate lab for Informatika praktikum
                    if ($mk['id_prodi'] === 'p1' && $mk['jenis'] === 'praktikum' && !in_array($r['id_ruangan'], ['l1', 'l2'])) continue;

                    // Check for conflicts
                    $conflict = false;
                    foreach ($jadwal as $j) {
                        if ($j['id_slot'] === $slot['id_slot'] && (
                            $j['id_ruangan'] === $r['id_ruangan'] ||
                            $j['id_kelas'] === $mk['id_kelas'] ||
                            $j['id_dosen'] === $mk['id_dosen']
                        )) {
                            $conflict = true;
                            break;
                        }
                    }

                    if (!$conflict) {
                        $jadwal[] = [
                            'id_jadwal' => 'j' . $id_jadwal++,
                            'semester' => $semester,
                            'tanggal_pembuatan' => $tanggal_pembuatan,
                            'id_mk' => $mk['id_mk'],
                            'id_kelas' => $mk['id_kelas'],
                            'id_ruangan' => $r['id_ruangan'],
                            'id_slot' => $slot['id_slot'],
                            'id_dosen' => $mk['id_dosen']
                        ];
                        $assigned = true;
                        break;
                    }
                }
                if ($assigned) break;
            }

            if (!$assigned) {
                $_SESSION['error'] = "Gagal menjadwalkan mata kuliah: " . htmlspecialchars($mk['nama']);
                header('Location: index.php');
                exit;
            }
        }

        // Insert jadwal to database
        $stmt = $pdo->prepare("INSERT INTO jadwal (id_jadwal, semester, tanggal_pembuatan, id_mk, id_kelas, id_ruangan, id_slot, id_dosen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($jadwal as $j) {
            $stmt->execute([$j['id_jadwal'], $j['semester'], $j['tanggal_pembuatan'], $j['id_mk'], $j['id_kelas'], $j['id_ruangan'], $j['id_slot'], $j['id_dosen']]);
        }

        header('Location: jadwal.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . htmlspecialchars($e->getMessage());
        header('Location: index.php');
        exit;
    }
}
?>