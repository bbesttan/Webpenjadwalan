<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

try {
    $prodi = $pdo->query("SELECT * FROM prodi")->fetchAll(PDO::FETCH_ASSOC);
    $jumlah_prodi = count($prodi);
    $jumlah_dosen = $pdo->query("SELECT COUNT(*) FROM dosen")->fetchColumn();
    $kelas_per_prodi = $pdo->query("SELECT p.nama, COUNT(k.id_kelas) as jumlah_kelas FROM prodi p LEFT JOIN kelas k ON p.id_prodi = k.id_prodi GROUP BY p.id_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $mk_per_prodi = $pdo->query("SELECT p.nama, COUNT(m.id_mk) as jumlah_mata_kuliah FROM prodi p LEFT JOIN mata_kuliah m ON p.id_prodi = m.id_prodi GROUP BY p.id_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $dosen = $pdo->query("SELECT d.id_dosen, d.nama, GROUP_CONCAT(m.nama SEPARATOR ', ') AS mata_kuliah FROM dosen d LEFT JOIN mata_kuliah m ON d.id_dosen = m.id_dosen GROUP BY d.id_dosen")->fetchAll(PDO::FETCH_ASSOC);
    $can_show_schedule = $pdo->query("SELECT COUNT(*) FROM prodi WHERE status = 'complete'")->fetchColumn() == $jumlah_prodi;
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
    <title>Dashboard - Webpenjadwalan</title>
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
                        <a href="index.php" class="nav-link text-white active bg-secondary py-2 px-3 mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="kelola.php" class="nav-link text-white hover-bg-secondary py-2 px-3 mb-2"><i class="fas fa-cog me-2"></i>Kelola Data</a>
                    </li>
                    <li class="nav-item">
                        <a href="jadwal.php" class="nav-link text-white hover-bg-secondary py-2 px-3 mb-2"><i class="fas fa-table me-2"></i>Hasil Jadwal</a>
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
            <h2 class="text-dark mb-4 animate__animated animate__fadeInUp">Dashboard</h2>
            <?php if ($error) { ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>
            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm animate__animated animate__fadeInUp">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-university me-2"></i>Jumlah Prodi</h5>
                            <h3 class="text-primary"><?php echo $jumlah_prodi; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm animate__animated animate__fadeInUp">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-chalkboard-teacher me-2"></i>Jumlah Dosen</h5>
                            <h3 class="text-primary"><?php echo $jumlah_dosen; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm animate__animated animate__fadeInUp">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-table me-2"></i>Tampilkan Jadwal</h5>
                            <?php if ($can_show_schedule) { ?>
                                <a href="jadwal.php" class="btn btn-primary"><i class="fas fa-eye me-2"></i>Tampilkan</a>
                            <?php } else { ?>
                                <p class="text-danger">Lengkapi data prodi di <a href="kelola_jadwal.php" class="text-primary">Kelola Jadwal</a>.</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Status Prodi -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-university me-2"></i>Status Prodi</h5>
                    <table id="prodiTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Prodi</th>
                                <th>Status</th>
                                <th>Jumlah Kelas</th>
                                <th>Jumlah Mata Kuliah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prodi as $p) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['nama']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $p['status'] == 'complete' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php
                                        $jumlah_kelas = 0;
                                        foreach ($kelas_per_prodi as $k) {
                                            if ($k['nama'] == $p['nama']) {
                                                $jumlah_kelas = $k['jumlah_kelas'];
                                                break;
                                            }
                                        }
                                        echo $jumlah_kelas;
                                    ?></td>
                                    <td><?php
                                        $jumlah_mk = 0;
                                        foreach ($mk_per_prodi as $m) {
                                            if ($m['nama'] == $p['nama']) {
                                                $jumlah_mk = $m['jumlah_mata_kuliah'];
                                                break;
                                            }
                                        }
                                        echo $jumlah_mk;
                                    ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Daftar Dosen -->
            <div class="card shadow-lg animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chalkboard-teacher me-2"></i>Daftar Dosen</h5>
                    <table id="dosenTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Dosen</th>
                                <th>Mata Kuliah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dosen as $d) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($d['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($d['mata_kuliah'] ?: 'Belum ada'); ?></td>
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
            $('#prodiTable').DataTable({ responsive: true });
            $('#dosenTable').DataTable({ responsive: true });
        });
    </script>
</body>
</html>