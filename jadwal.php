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
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['error']);
} catch (PDOException $e) {
    $error = "Error database: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Jadwal - Webpenjadwalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="bg-dark text-white vh-100 sidebar shadow-lg" style="width: 240px;">
            <div class="p-4">
                <h3 class="text-white mb-4"><i class="fas fa-calendar-alt me-2"></i>Penjadwalan</h3>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link text-white hover-bg-secondary py-2 px-3 mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="kelola.php" class="nav-link text-white hover-bg-secondary py-2 px-3 mb-2"><i class="fas fa-cog me-2"></i>Kelola</a>
                    </li>
                    <li class="nav-item">
                        <a href="jadwal.php" class="nav-link text-white active bg-secondary py-2 px-3 mb-2"><i class="fas fa-table me-2"></i>Hasil Jadwal</a>
                    </li>
                    <li class="nav-item">
                        <a href="kelola_jadwal.php" class="nav-link text-white hover-bg-secondary py-2 px-3 mb-2"><i class="fas fa-calendar-plus me-2"></i>Kelola Jadwal</a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link text-white hover-bg-secondary py-2 px-3"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Main Content -->
        <div class="flex-grow-1 p-4 ms-240px">
            <h2 class="text-dark mb-4 animate__animated animate__fadeInUp">Hasil Jadwal</h2>
            <?php if ($error) { ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>
            <div class="card shadow-lg animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-table me-2"></i>Jadwal Kuliah</h5>
                    <a href="export.php" class="btn btn-primary mb-3"><i class="fas fa-file-excel me-2"></i>Ekspor ke Excel</a>
                    <table id="jadwalTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Semester</th>
                                <th>Mata Kuliah</th>
                                <th>Kelas</th>
                                <th>Ruangan</th>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Dosen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwal as $j) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($j['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($j['nama_mk']); ?></td>
                                    <td><?php echo htmlspecialchars($j['nama_kelas']); ?></td>
                                    <td><?php echo htmlspecialchars($j['nama_ruangan']); ?></td>
                                    <td><?php echo htmlspecialchars($j['hari']); ?></td>
                                    <td><?php echo htmlspecialchars($j['jam']); ?></td>
                                    <td><?php echo htmlspecialchars($j['nama_dosen']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#jadwalTable').DataTable({ responsive: true });
        });
    </script>
</body>
</html>