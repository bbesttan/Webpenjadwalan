<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$preview_jadwal = $_SESSION['preview_jadwal'] ?? [];
$edit_schedule = null;

// Fetch data for forms and generation
try {
    $prodi = $pdo->query("SELECT * FROM prodi")->fetchAll(PDO::FETCH_ASSOC);
    $slot_waktu = $pdo->query("SELECT * FROM slot_waktu")->fetchAll(PDO::FETCH_ASSOC);
    $ruangan = $pdo->query("SELECT * FROM ruangan")->fetchAll(PDO::FETCH_ASSOC);
    $courses = $pdo->query("SELECT id_mk, nama FROM mata_kuliah")->fetchAll(PDO::FETCH_ASSOC);
    $classes = $pdo->query("SELECT id_kelas, nama FROM kelas")->fetchAll(PDO::FETCH_ASSOC);
    $lecturers = $pdo->query("SELECT id_dosen, nama FROM dosen")->fetchAll(PDO::FETCH_ASSOC);
    $jumlah_prodi = count($prodi);
    $jumlah_prodi_complete = $pdo->query("SELECT COUNT(*) FROM prodi WHERE status = 'complete'")->fetchColumn();
} catch (PDOException $e) {
    $error = "Kesalahan database: " . htmlspecialchars($e->getMessage());
}

// Fetch schedules for management
try {
    $stmt = $pdo->query("
        SELECT j.id_jadwal, j.semester, j.tanggal_pembuatan, m.nama as course_name, k.nama as class_name, 
               r.nama as room_name, s.hari as day, s.jam as time, d.nama as lecturer_name
        FROM jadwal j
        JOIN mata_kuliah m ON j.id_mk = m.id_mk
        JOIN kelas k ON j.id_kelas = k.id_kelas
        JOIN ruangan r ON j.id_ruangan = r.id_ruangan
        JOIN slot_waktu s ON j.id_slot = s.id_slot
        JOIN dosen d ON j.id_dosen = d.id_dosen
    ");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal mengambil jadwal: " . htmlspecialchars($e->getMessage());
}

// Handle edit mode
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM jadwal WHERE id_jadwal = ?");
        $stmt->execute([$id]);
        $edit_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_schedule) {
            $error = "Jadwal tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error = "Gagal mengambil data jadwal: " . htmlspecialchars($e->getMessage());
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING);
        $tanggal_pembuatan = $_POST['tanggal_pembuatan'] ?? '';
        $id_mk = $_POST['id_mk'] ?? '';
        $id_kelas = $_POST['id_kelas'] ?? '';
        $id_ruangan = $_POST['id_ruangan'] ?? '';
        $id_slot = $_POST['id_slot'] ?? '';
        $id_dosen = $_POST['id_dosen'] ?? '';

        if (empty($semester) || empty($tanggal_pembuatan) || empty($id_mk) || empty($id_kelas) || 
            empty($id_ruangan) || empty($id_slot) || empty($id_dosen)) {
            $error = "Semua kolom wajib diisi.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO jadwal (semester, tanggal_pembuatan, id_mk, id_kelas, id_ruangan, id_slot, id_dosen) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$semester, $tanggal_pembuatan, $id_mk, $id_kelas, $id_ruangan, $id_slot, $id_dosen]);
                header('Location: kelola_jadwal.php');
                exit;
            } catch (PDOException $e) {
                $error = "Gagal menambah jadwal: " . htmlspecialchars($e->getMessage());
            }
        }
    } elseif (isset($_POST['update_schedule'])) {
        $id = $_POST['id_jadwal'] ?? '';
        $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING);
        $tanggal_pembuatan = $_POST['tanggal_pembuatan'] ?? '';
        $id_mk = $_POST['id_mk'] ?? '';
        $id_kelas = $_POST['id_kelas'] ?? '';
        $id_ruangan = $_POST['id_ruangan'] ?? '';
        $id_slot = $_POST['id_slot'] ?? '';
        $id_dosen = $_POST['id_dosen'] ?? '';

        if (empty($id) || empty($semester) || empty($tanggal_pembuatan) || empty($id_mk) || 
            empty($id_kelas) || empty($id_ruangan) || empty($id_slot) || empty($id_dosen)) {
            $error = "Semua kolom wajib diisi.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE jadwal SET semester = ?, tanggal_pembuatan = ?, id_mk = ?, 
                                       id_kelas = ?, id_ruangan = ?, id_slot = ?, id_dosen = ? WHERE id_jadwal = ?");
                $stmt->execute([$semester, $tanggal_pembuatan, $id_mk, $id_kelas, $id_ruangan, $id_slot, $id_dosen, $id]);
                header('Location: kelola_jadwal.php');
                exit;
            } catch (PDOException $e) {
                $error = "Gagal memperbarui jadwal: " . htmlspecialchars($e->getMessage());
            }
        }
    } elseif (isset($_POST['delete_schedule']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM jadwal WHERE id_jadwal = ?");
            $stmt->execute([$id]);
            header('Location: kelola_jadwal.php');
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menghapus jadwal: " . htmlspecialchars($e->getMessage());
        }
    } elseif (isset($_POST['generate'])) {
        $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING);
        $id_prodi = $_POST['id_prodi'] ?? [];
        $id_slots = $_POST['id_slots'] ?? [];
        $tanggal_pembuatan = date('Y-m-d');

        if (empty($semester) || empty($id_prodi) || empty($id_slots)) {
            $error = "Harap lengkapi semua field: semester, prodi, dan slot waktu.";
        } else {
            try {
                $placeholders = implode(',', array_fill(0, count($id_prodi), '?'));
                $stmt = $pdo->prepare("SELECT m.*, k.id_kelas, k.jumlah_mahasiswa, p.nama AS nama_prodi 
                                       FROM mata_kuliah m 
                                       JOIN kelas k ON m.id_prodi = k.id_prodi 
                                       JOIN prodi p ON m.id_prodi = p.id_prodi 
                                       WHERE m.id_prodi IN ($placeholders)");
                $stmt->execute($id_prodi);
                $mata_kuliah = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $jadwal = [];
                $id_jadwal = 1;

                foreach ($mata_kuliah as $mk) {
                    shuffle($ruangan);
                    shuffle($slot_waktu);
                    $assigned = false;

                    foreach ($slot_waktu as $slot) {
                        if (!in_array($slot['id_slot'], $id_slots)) continue;

                        foreach ($ruangan as $r) {
                            if ($mk['jumlah_mahasiswa'] > $r['kapasitas']) continue;
                            if ($mk['id_prodi'] === 'p1' && $mk['jenis'] === 'praktikum' && !in_array($r['id_ruangan'], ['r1', 'r2'])) continue;

                            $conflict = false;
                            foreach ($jadwal as $j) {
                                if ($j['id_slot'] === $slot['id_slot'] &&
                                    ($j['id_ruangan'] === $r['id_ruangan'] ||
                                     $j['id_kelas'] === $mk['id_kelas'] ||
                                     $j['id_dosen'] === $mk['id_dosen'])) {
                                    $conflict = true;
                                    break;
                                }
                            }

                            if (!$conflict) {
                                $jadwal[] = [
                                    'id_jadwal' => 'j' . ($id_jadwal++),
                                    'semester' => $semester,
                                    'tanggal_pembuatan' => $tanggal_pembuatan,
                                    'id_mk' => $mk['id_mk'],
                                    'nama_mk' => $mk['nama'],
                                    'id_kelas' => $mk['id_kelas'],
                                    'nama_prodi' => $mk['nama_prodi'],
                                    'id_ruangan' => $r['id_ruangan'],
                                    'nama_ruangan' => $r['nama'],
                                    'id_slot' => $slot['id_slot'],
                                    'hari' => $slot['hari'],
                                    'jam' => $slot['jam'],
                                    'id_dosen' => $mk['id_dosen'],
                                    'nama_dosen' => $pdo->query("SELECT nama FROM dosen WHERE id_dosen = '{$mk['id_dosen']}'")->fetchColumn()
                                ];
                                $assigned = true;
                                break;
                            }
                        }
                        if ($assigned) break;
                    }

                    if (!$assigned) {
                        $error = "Gagal menjadwalkan mata kuliah: " . htmlspecialchars($mk['nama']) . " karena konflik ruangan, kelas, atau dosen.";
                        break;
                    }
                }

                if (empty($error)) {
                    $_SESSION['preview_jadwal'] = $jadwal;
                    header('Location: kelola_jadwal.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Kesalahan saat mengambil data: " . htmlspecialchars($e->getMessage());
            }
        }
    } elseif (isset($_POST['save']) && !empty($preview_jadwal)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO jadwal (semester, tanggal_pembuatan, id_mk, id_kelas, id_ruangan, id_slot, id_dosen)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($preview_jadwal as $j) {
                $stmt->execute([
                    $j['semester'],
                    $j['tanggal_pembuatan'],
                    $j['id_mk'],
                    $j['id_kelas'],
                    $j['id_ruangan'],
                    $j['id_slot'],
                    $j['id_dosen']
                ]);
            }
            $pdo->commit();
            unset($_SESSION['preview_jadwal']);
            header('Location: kelola_jadwal.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal menyimpan jadwal: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - Webpenjadwalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-none d-md-block bg-light sidebar">
                <div class="sidebar-sticky">
                    <h5 class="sidebar-heading">Webpenjadwalan</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="kelola.php"><i class="fas fa-cog"></i> Kelola Data</a></li>
                        <li class="nav-item"><a class="nav-link" href="jadwal.php"><i class="fas fa-calendar"></i> Hasil Jadwal</a></li>
                        <li class="nav-item"><a class="nav-link active" href="kelola_jadwal.php"><i class="fas fa-calendar-alt"></i> Kelola Jadwal</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4 animate__animated animate__fadeIn">Kelola Jadwal</h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX"><?php echo $error; ?></div>
                <?php endif; ?>
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" id="generate-tab" data-bs-toggle="tab" href="#generate">Generate Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="manage-tab" data-bs-toggle="tab" href="#manage">Manage Schedules</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="generate">
                        <?php if ($jumlah_prodi_complete == $jumlah_prodi): ?>
                            <form method="POST" class="card p-4 mb-4 animate__animated animate__fadeIn">
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select name="semester" id="semester" class="form-select">
                                        <option value="Ganjil 2025">Ganjil 2025</option>
                                        <option value="Genap 2025">Genap 2025</option>
                                        <option value="Ganjil 2026">Ganjil 2026</option>
                                        <option value="Genap 2026">Genap 2026</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Program Studi</label>
                                    <?php foreach ($prodi as $p): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="id_prodi[]" value="<?php echo $p['id_prodi']; ?>">
                                            <label class="form-check-label"><?php echo htmlspecialchars($p['nama']); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slot Waktu</label>
                                    <?php foreach ($slot_waktu as $slot): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="id_slots[]" value="<?php echo $slot['id_slot']; ?>">
                                            <label class="form-check-label"><?php echo htmlspecialchars($slot['hari'] . ' ' . $slot['jam']); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" name="generate" class="btn btn-primary">Generate Jadwal</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">Semua prodi harus berstatus 'complete' untuk membuat jadwal.</div>
                        <?php endif; ?>
                        <?php if (!empty($preview_jadwal)): ?>
                            <h3 class="mt-4">Pratinjau Jadwal</h3>
                            <table class="table table-striped" id="previewTable">
                                <thead>
                                    <tr>
                                        <th>Semester</th>
                                        <th>Mata Kuliah</th>
                                        <th>Prodi</th>
                                        <th>Ruangan</th>
                                        <th>Hari</th>
                                        <th>Jam</th>
                                        <th>Dosen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview_jadwal as $j): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($j['semester']); ?></td>
                                            <td><?php echo htmlspecialchars($j['nama_mk']); ?></td>
                                            <td><?php echo htmlspecialchars($j['nama_prodi']); ?></td>
                                            <td><?php echo htmlspecialchars($j['nama_ruangan']); ?></td>
                                            <td><?php echo htmlspecialchars($j['hari']); ?></td>
                                            <td><?php echo htmlspecialchars($j['jam']); ?></td>
                                            <td><?php echo htmlspecialchars($j['nama_dosen']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <form method="POST">
                                <button type="submit" name="save" class="btn btn-success">Simpan Jadwal</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="manage">
                        <h3 class="mt-4">Kelola Jadwal</h3>
                        <a href="#addScheduleModal" class="btn btn-primary mb-3" data-bs-toggle="modal">Tambah Jadwal Baru</a>
                        <table class="table table-striped" id="scheduleTable">
                            <thead>
                                <tr>
                                    <th>ID Jadwal</th>
                                    <th>Semester</th>
                                    <th>Tanggal Pembuatan</th>
                                    <th>Mata Kuliah</th>
                                    <th>Kelas</th>
                                    <th>Ruangan</th>
                                    <th>Hari</th>
                                    <th>Jam</th>
                                    <th>Dosen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['id_jadwal']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['semester']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['tanggal_pembuatan']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['room_name']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['day']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['time']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['lecturer_name']); ?></td>
                                        <td>
                                            <a href="kelola_jadwal.php?action=edit&id=<?php echo urlencode($schedule['id_jadwal']); ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($schedule['id_jadwal']); ?>">
                                                <button type="submit" name="delete_schedule" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal untuk Tambah Jadwal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">Tambah Jadwal Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" name="semester" id="semester" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal_pembuatan" class="form-label">Tanggal Pembuatan</label>
                            <input type="date" name="tanggal_pembuatan" id="tanggal_pembuatan" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_mk" class="form-label">Mata Kuliah</label>
                            <select name="id_mk" id="id_mk" class="form-select" required>
                                <option value="">Pilih Mata Kuliah</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id_mk']; ?>"><?php echo htmlspecialchars($course['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_kelas" class="form-label">Kelas</label>
                            <select name="id_kelas" id="id_kelas" class="form-select" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id_kelas']; ?>"><?php echo htmlspecialchars($class['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_ruangan" class="form-label">Ruangan</label>
                            <select name="id_ruangan" id="id_ruangan" class="form-select" required>
                                <option value="">Pilih Ruangan</option>
                                <?php foreach ($ruangan as $room): ?>
                                    <option value="<?php echo $room['id_ruangan']; ?>"><?php echo htmlspecialchars($room['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_slot" class="form-label">Slot Waktu</label>
                            <select name="id_slot" id="id_slot" class="form-select" required>
                                <option value="">Pilih Slot Waktu</option>
                                <?php foreach ($slot_waktu as $slot): ?>
                                    <option value="<?php echo $slot['id_slot']; ?>"><?php echo htmlspecialchars($slot['hari'] . ' ' . $slot['jam']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_dosen" class="form-label">Dosen</label>
                            <select name="id_dosen" id="id_dosen" class="form-select" required>
                                <option value="">Pilih Dosen</option>
                                <?php foreach ($lecturers as $lecturer): ?>
                                    <option value="<?php echo $lecturer['id_dosen']; ?>"><?php echo htmlspecialchars($lecturer['nama']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_schedule" class="btn btn-primary">Tambah Jadwal</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script untuk DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm"> </script>
</body>
</html>