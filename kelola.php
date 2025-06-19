<?php
require_once 'config.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

try {
    $prodi = $pdo->query("SELECT * FROM prodi")->fetchAll(PDO::FETCH_ASSOC);
    $dosen = $pdo->query("SELECT * FROM dosen")->fetchAll(PDO::FETCH_ASSOC);
    $ruangan = $pdo->query("SELECT * FROM ruangan")->fetchAll(PDO::FETCH_ASSOC);
    $slot_waktu = $pdo->query("SELECT * FROM slot_waktu")->fetchAll(PDO::FETCH_ASSOC);
    $kelas = $pdo->query("SELECT k.*, p.nama as nama_prodi FROM kelas k JOIN prodi p ON k.id_prodi = p.id_prodi")->fetchAll(PDO::FETCH_ASSOC);
    $mata_kuliah = $pdo->query("SELECT m.*, p.nama as nama_prodi, d.nama as nama_dosen FROM mata_kuliah m JOIN prodi p ON m.id_prodi = p.id_prodi JOIN dosen d ON m.id_dosen = d.id_dosen")->fetchAll(PDO::FETCH_ASSOC);
    $error = $_SESSION['error'] ?? '';
    unset($_SESSION['error']);
} catch (PDOException $e) {
    $error = "Error database: " . htmlspecialchars($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Tambah
        if (isset($_POST['tambah_prodi'])) {
            $id_prodi = 'p' . (count($prodi) + 1);
            $nama = $_POST['nama_prodi'];
            $stmt = $pdo->prepare("INSERT INTO prodi (id_prodi, nama, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$id_prodi, $nama]);
        } elseif (isset($_POST['tambah_dosen'])) {
            $id_dosen = 'd' . (count($dosen) + 1);
            $nama = $_POST['nama_dosen'];
            $stmt = $pdo->prepare("INSERT INTO dosen (id_dosen, nama) VALUES (?, ?)");
            $stmt->execute([$id_dosen, $nama]);
        } elseif (isset($_POST['tambah_kelas'])) {
            $id_kelas = 'k' . (count($kelas) + 1);
            $nama = $_POST['nama_kelas'];
            $id_prodi = $_POST['id_prodi'];
            $jumlah_mahasiswa = $_POST['jumlah_mahasiswa'];
            $stmt = $pdo->prepare("INSERT INTO kelas (id_kelas, nama, id_prodi, jumlah_mahasiswa) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_kelas, $nama, $id_prodi, $jumlah_mahasiswa]);
        } elseif (isset($_POST['tambah_mata_kuliah'])) {
            $id_mk = 'mk' . (count($mata_kuliah) + 1);
            $nama = $_POST['nama_mk'];
            $id_prodi = $_POST['id_prodi'];
            $jenis = $_POST['jenis'];
            $durasi = $_POST['durasi'];
            $id_dosen = $_POST['id_dosen'];
            $stmt = $pdo->prepare("INSERT INTO mata_kuliah (id_mk, nama, id_prodi, jenis, durasi, id_dosen) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_mk, $nama, $id_prodi, $jenis, $durasi, $id_dosen]);
        } elseif (isset($_POST['tambah_ruangan'])) {
            $id_ruangan = 'r' . (count($ruangan) + 1);
            $nama = $_POST['nama_ruangan'];
            $kapasitas = $_POST['kapasitas'];
            $stmt = $pdo->prepare("INSERT INTO ruangan (id_ruangan, nama, kapasitas) VALUES (?, ?, ?)");
            $stmt->execute([$id_ruangan, $nama, $kapasitas]);
        } elseif (isset($_POST['tambah_slot_waktu'])) {
            $id_slot = 's' . (count($slot_waktu) + 1);
            $hari = $_POST['hari'];
            $jam = $_POST['jam'];
            $stmt = $pdo->prepare("INSERT INTO slot_waktu (id_slot, hari, jam) VALUES (?, ?, ?)");
            $stmt->execute([$id_slot, $hari, $jam]);
        }
        // Edit
        elseif (isset($_POST['edit_prodi'])) {
            $id_prodi = $_POST['id_prodi'];
            $nama = $_POST['nama_prodi'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE prodi SET nama = ?, status = ? WHERE id_prodi = ?");
            $stmt->execute([$nama, $status, $id_prodi]);
        } elseif (isset($_POST['edit_dosen'])) {
            $id_dosen = $_POST['id_dosen'];
            $nama = $_POST['nama_dosen'];
            $stmt = $pdo->prepare("UPDATE dosen SET nama = ? WHERE id_dosen = ?");
            $stmt->execute([$nama, $id_dosen]);
        } elseif (isset($_POST['edit_kelas'])) {
            $id_kelas = $_POST['id_kelas'];
            $nama = $_POST['nama_kelas'];
            $id_prodi = $_POST['id_prodi'];
            $jumlah_mahasiswa = $_POST['jumlah_mahasiswa'];
            $stmt = $pdo->prepare("UPDATE kelas SET nama = ?, id_prodi = ?, jumlah_mahasiswa = ? WHERE id_kelas = ?");
            $stmt->execute([$nama, $id_prodi, $jumlah_mahasiswa, $id_kelas]);
        } elseif (isset($_POST['edit_mata_kuliah'])) {
            $id_mk = $_POST['id_mk'];
            $nama = $_POST['nama_mk'];
            $id_prodi = $_POST['id_prodi'];
            $jenis = $_POST['jenis'];
            $durasi = $_POST['durasi'];
            $id_dosen = $_POST['id_dosen'];
            $stmt = $pdo->prepare("UPDATE mata_kuliah SET nama = ?, id_prodi = ?, jenis = ?, durasi = ?, id_dosen = ? WHERE id_mk = ?");
            $stmt->execute([$nama, $id_prodi, $jenis, $durasi, $id_dosen, $id_mk]);
        } elseif (isset($_POST['edit_ruangan'])) {
            $id_ruangan = $_POST['id_ruangan'];
            $nama = $_POST['nama_ruangan'];
            $kapasitas = $_POST['kapasitas'];
            $stmt = $pdo->prepare("UPDATE ruangan SET nama = ?, kapasitas = ? WHERE id_ruangan = ?");
            $stmt->execute([$nama, $kapasitas, $id_ruangan]);
        } elseif (isset($_POST['edit_slot_waktu'])) {
            $id_slot = $_POST['id_slot'];
            $hari = $_POST['hari'];
            $jam = $_POST['jam'];
            $stmt = $pdo->prepare("UPDATE slot_waktu SET hari = ?, jam = ? WHERE id_slot = ?");
            $stmt->execute([$hari, $jam, $id_slot]);
        }
        // Hapus
        elseif (isset($_POST['hapus_prodi'])) {
            $id_prodi = $_POST['id_prodi'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE id_prodi = ?");
            $stmt->execute([$id_prodi]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Prodi tidak dapat dihapus karena memiliki kelas terkait.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM prodi WHERE id_prodi = ?");
                $stmt->execute([$id_prodi]);
            }
        } elseif (isset($_POST['hapus_dosen'])) {
            $id_dosen = $_POST['id_dosen'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM mata_kuliah WHERE id_dosen = ?");
            $stmt->execute([$id_dosen]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Dosen tidak dapat dihapus karena memiliki mata kuliah terkait.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM dosen WHERE id_dosen = ?");
                $stmt->execute([$id_dosen]);
            }
        } elseif (isset($_POST['hapus_kelas'])) {
            $id_kelas = $_POST['id_kelas'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE id_kelas = ?");
            $stmt->execute([$id_kelas]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Kelas tidak dapat dihapus karena memiliki jadwal terkait.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM kelas WHERE id_kelas = ?");
                $stmt->execute([$id_kelas]);
            }
        } elseif (isset($_POST['hapus_mata_kuliah'])) {
            $id_mk = $_POST['id_mk'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE id_mk = ?");
            $stmt->execute([$id_mk]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Mata kuliah tidak dapat dihapus karena memiliki jadwal terkait.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM mata_kuliah WHERE id_mk = ?");
                $stmt->execute([$id_mk]);
            }
        } elseif (isset($_POST['hapus_ruangan'])) {
            $id_ruangan = $_POST['id_ruangan'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE id_ruangan = ?");
            $stmt->execute([$id_ruangan]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Ruangan tidak dapat dihapus karena memiliki jadwal terkait.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM ruangan WHERE id_ruangan = ?");
                $stmt->execute([$id_ruangan]);
            }
        } elseif (isset($_POST['hapus_slot_waktu'])) {
            $id_slot = $_POST['id_slot'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE id_slot = ?");
            $stmt->execute([$id_slot]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Slot waktu tidak dapat dihapus karena memiliki jadwal terkait.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM slot_waktu WHERE id_slot = ?");
                $stmt->execute([$id_slot]);
            }
        }
        header('Location: kelola.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . htmlspecialchars($e->getMessage());
        header('Location: kelola.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data - Webpenjadwalan</title>
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
                        <a href="kelola.php" class="nav-link text-white active bg-secondary py-2 px-3 mb-2"><i class="fas fa-cog me-2"></i>Kelola Data</a>
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
            <h2 class="text-dark mb-4 animate__animated animate__fadeInUp">Kelola Data</h2>
            <?php if ($error) { ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>
            <!-- Prodi -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-university me-2"></i>Prodi</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Aksi</th>
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
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProdiModal<?php echo $p['id_prodi']; ?>"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus prodi ini?');">
                                            <input type="hidden" name="id_prodi" value="<?php echo $p['id_prodi']; ?>">
                                            <button type="submit" name="hapus_prodi" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Modal Edit Prodi -->
                                <div class="modal fade" id="editProdiModal<?php echo $p['id_prodi']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Prodi</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id_prodi" value="<?php echo $p['id_prodi']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Prodi</label>
                                                        <input type="text" name="nama_prodi" class="form-control" value="<?php echo htmlspecialchars($p['nama']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="pending" <?php echo $p['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="complete" <?php echo $p['status'] == 'complete' ? 'selected' : ''; ?>>Complete</option>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="edit_prodi" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Tambah Prodi</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Prodi</label>
                            <input type="text" name="nama_prodi" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah_prodi" class="btn btn-primary">Tambah</button>
                    </form>
                </div>
            </div>
            <!-- Dosen -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chalkboard-teacher me-2"></i>Dosen</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dosen as $d) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($d['nama']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editDosenModal<?php echo $d['id_dosen']; ?>"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus dosen ini?');">
                                            <input type="hidden" name="id_dosen" value="<?php echo $d['id_dosen']; ?>">
                                            <button type="submit" name="hapus_dosen" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Modal Edit Dosen -->
                                <div class="modal fade" id="editDosenModal<?php echo $d['id_dosen']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Dosen</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id_dosen" value="<?php echo $d['id_dosen']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Dosen</label>
                                                        <input type="text" name="nama_dosen" class="form-control" value="<?php echo htmlspecialchars($d['nama']); ?>" required>
                                                    </div>
                                                    <button type="submit" name="edit_dosen" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Tambah Dosen</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Dosen</label>
                            <input type="text" name="nama_dosen" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah_dosen" class="btn btn-primary">Tambah</button>
                    </form>
                </div>
            </div>
            <!-- Kelas -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users me-2"></i>Kelas</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Prodi</th>
                                <th>Jumlah Mahasiswa</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kelas as $k) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($k['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($k['nama_prodi']); ?></td>
                                    <td><?php echo $k['jumlah_mahasiswa']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editKelasModal<?php echo $k['id_kelas']; ?>"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus kelas ini?');">
                                            <input type="hidden" name="id_kelas" value="<?php echo $k['id_kelas']; ?>">
                                            <button type="submit" name="hapus_kelas" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Modal Edit Kelas -->
                                <div class="modal fade" id="editKelasModal<?php echo $k['id_kelas']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Kelas</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id_kelas" value="<?php echo $k['id_kelas']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Kelas</label>
                                                        <input type="text" name="nama_kelas" class="form-control" value="<?php echo htmlspecialchars($k['nama']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Prodi</label>
                                                        <select name="id_prodi" class="form-select" required>
                                                            <?php foreach ($prodi as $p) { ?>
                                                                <option value="<?php echo $p['id_prodi']; ?>" <?php echo $p['id_prodi'] == $k['id_prodi'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($p['nama']); ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jumlah Mahasiswa</label>
                                                        <input type="number" name="jumlah_mahasiswa" class="form-control" value="<?php echo $k['jumlah_mahasiswa']; ?>" required>
                                                    </div>
                                                    <button type="submit" name="edit_kelas" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Tambah Kelas</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Kelas</label>
                            <input type="text" name="nama_kelas" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prodi</label>
                            <select name="id_prodi" class="form-select" required>
                                <?php foreach ($prodi as $p) { ?>
                                    <option value="<?php echo htmlspecialchars($p['id_prodi']); ?>"><?php echo htmlspecialchars($p['nama']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Mahasiswa</label>
                            <input type="number" name="jumlah_mahasiswa" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah_kelas" class="btn btn-primary">Tambah</button>
                    </form>
                </div>
            </div>
            <!-- Mata Kuliah -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book me-2"></i>Mata Kuliah</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Prodi</th>
                                <th>Jenis</th>
                                <th>Durasi (jam)</th>
                                <th>Dosen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mata_kuliah as $m) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($m['nama_prodi']); ?></td>
                                    <td><?php echo ucfirst($m['jenis']); ?></td>
                                    <td><?php echo $m['durasi']; ?></td>
                                    <td><?php echo htmlspecialchars($m['nama_dosen']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editMataKuliahModal<?php echo $m['id_mk']; ?>"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?');">
                                            <input type="hidden" name="id_mk" value="<?php echo $m['id_mk']; ?>">
                                            <button type="submit" name="hapus_mata_kuliah" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Modal Edit Mata Kuliah -->
                                <div class="modal fade" id="editMataKuliahModal<?php echo $m['id_mk']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Mata Kuliah</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id_mk" value="<?php echo $m['id_mk']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Mata Kuliah</label>
                                                        <input type="text" name="nama_mk" class="form-control" value="<?php echo htmlspecialchars($m['nama']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Prodi</label>
                                                        <select name="id_prodi" class="form-select" required>
                                                            <?php foreach ($prodi as $p) { ?>
                                                                <option value="<?php echo $p['id_prodi']; ?>" <?php echo $p['id_prodi'] == $m['id_prodi'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($p['nama']); ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jenis</label>
                                                        <select name="jenis" class="form-select" required>
                                                            <option value="teori" <?php echo $m['jenis'] == 'teori' ? 'selected' : ''; ?>>Teori</option>
                                                            <option value="praktikum" <?php echo $m['jenis'] == 'praktikum' ? 'selected' : ''; ?>>Praktikum</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Durasi (jam)</label>
                                                        <input type="number" step="0.5" name="durasi" class="form-control" value="<?php echo $m['durasi']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Dosen</label>
                                                        <select name="id_dosen" class="form-select" required>
                                                            <?php foreach ($dosen as $d) { ?>
                                                                <option value="<?php echo $d['id_dosen']; ?>" <?php echo $d['id_dosen'] == $m['id_dosen'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($d['nama']); ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" name="edit_mata_kuliah" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Tambah Mata Kuliah</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Mata Kuliah</label>
                            <input type="text" name="nama_mk" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prodi</label>
                            <select name="id_prodi" class="form-select" required>
                                <?php foreach ($prodi as $p) { ?>
                                    <option value="<?php echo htmlspecialchars($p['id_prodi']); ?>"><?php echo htmlspecialchars($p['nama']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis</label>
                            <select name="jenis" class="form-select" required>
                                <option value="teori">Teori</option>
                                <option value="praktikum">Praktikum</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Durasi (jam)</label>
                            <input type="number" step="0.5" name="durasi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosen</label>
                            <select name="id_dosen" class="form-select" required>
                                <?php foreach ($dosen as $d) { ?>
                                    <option value="<?php echo htmlspecialchars($d['id_dosen']); ?>"><?php echo htmlspecialchars($d['nama']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <button type="submit" name="tambah_mata_kuliah" class="btn btn-primary">Tambah</button>
                    </form>
                </div>
            </div>
            <!-- Ruangan -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-door-open me-2"></i>Ruangan</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kapasitas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ruangan as $r) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['nama']); ?></td>
                                    <td><?php echo $r['kapasitas']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editRuanganModal<?php echo $r['id_ruangan']; ?>"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus ruangan ini?');">
                                            <input type="hidden" name="id_ruangan" value="<?php echo $r['id_ruangan']; ?>">
                                            <button type="submit" name="hapus_ruangan" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Modal Edit Ruangan -->
                                <div class="modal fade" id="editRuanganModal<?php echo $r['id_ruangan']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Ruangan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id_ruangan" value="<?php echo $r['id_ruangan']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Ruangan</label>
                                                        <input type="text" name="nama_ruangan" class="form-control" value="<?php echo htmlspecialchars($r['nama']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Kapasitas</label>
                                                        <input type="number" name="kapasitas" class="form-control" value="<?php echo $r['kapasitas']; ?>" required>
                                                    </div>
                                                    <button type="submit" name="edit_ruangan" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Tambah Ruangan</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Ruangan</label>
                            <input type="text" name="nama_ruangan" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kapasitas</label>
                            <input type="number" name="kapasitas" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah_ruangan" class="btn btn-primary">Tambah</button>
                    </form>
                </div>
            </div>
            <!-- Slot Waktu -->
            <div class="card shadow-lg mb-4 animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-clock me-2"></i>Slot Waktu</h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slot_waktu as $s) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['hari']); ?></td>
                                    <td><?php echo htmlspecialchars($s['jam']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSlotWaktuModal<?php echo $s['id_slot']; ?>"><i class="fas fa-edit"></i></button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus slot waktu ini?');">
                                            <input type="hidden" name="id_slot" value="<?php echo $s['id_slot']; ?>">
                                            <button type="submit" name="hapus_slot_waktu" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Modal Edit Slot Waktu -->
                                <div class="modal fade" id="editSlotWaktuModal<?php echo $s['id_slot']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="card-title">Edit Slot Waktu</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="id_slot" value="<?php echo $s['id_slot']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Hari</label>
                                                        <input type="text" name="hari" class="form-control" value="<?php echo htmlspecialchars($s['hari']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Jam</label>
                                                        <input type="text" name="jam" class="form-control" value="<?php echo htmlspecialchars($s['jam']); ?>" required>
                                                    </div>
                                                    <button type="submit" name="edit_slot_waktu" class="btn btn-primary">Simpan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Tambah Slot Waktu</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <input type="text" name="hari" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jam</label>
                            <input type="text" name="jam" class="form-control" required>
                        </div>
                        <button type="submit" name="tambah_slot_waktu" class="btn btn-primary">Tambah</button>
                    </form>
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
            $('table.table-hover').each(function() {
                $(this).DataTable({ responsive: true });
            });
        });
    </script>
</body>
</html>