<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$entity = $_GET['entity'] ?? 'prodi';
$prodi = $pdo->query("SELECT * FROM prodi")->fetchAll(PDO::FETCH_ASSOC);
$dosen = $pdo->query("SELECT * FROM dosen")->fetchAll(PDO::FETCH_ASSOC);
$entities = ['prodi', 'kelas', 'mata_kuliah', 'dosen'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($entity == 'prodi') {
        $id_prodi = uniqid('pr');
        $nama = $_POST['nama'];
        $pdo->prepare("INSERT INTO prodi (id_prodi, nama, status) VALUES (?, ?, 'pending')")->execute([$id_prodi, $nama]);
    } elseif ($entity == 'kelas') {
        $id_kelas = uniqid('kl');
        $nama = $_POST['nama'];
        $id_prodi = $_POST['id_prodi'];
        $jumlah_mahasiswa = (int)$_POST['jumlah_mahasiswa'];
        $pdo->prepare("INSERT INTO kelas (id_kelas, nama, id_prodi, jumlah_mahasiswa) VALUES (?, ?, ?, ?)")->execute([$id_kelas, $nama, $id_prodi, $jumlah_mahasiswa]);
        $pdo->prepare("UPDATE prodi SET status = 'complete' WHERE id_prodi = ? AND EXISTS (SELECT 1 FROM mata_kuliah WHERE id_prodi = ?)")->execute([$id_prodi, $id_prodi]);
    } elseif ($entity == 'mata_kuliah') {
        $id_mk = uniqid('mk');
        $nama = $_POST['nama'];
        $id_prodi = $_POST['id_prodi'];
        $jenis = $_POST['jenis'];
        $durasi = (float)$_POST['durasi'];
        $id_dosen = $_POST['id_dosen'];
        $pdo->prepare("INSERT INTO mata_kuliah (id_mk, nama, id_prodi, jenis, durasi, id_dosen) VALUES (?, ?, ?, ?, ?, ?)")->execute([$id_mk, $nama, $id_prodi, $jenis, $durasi, $id_dosen]);
        $pdo->prepare("UPDATE prodi SET status = 'complete' WHERE id_prodi = ? AND EXISTS (SELECT 1 FROM kelas WHERE id_prodi = ?)")->execute([$id_prodi, $id_prodi]);
    } elseif ($entity == 'dosen') {
        $id_dosen = uniqid('ds');
        $nama = $_POST['nama'];
        $pdo->prepare("INSERT INTO dosen (id_dosen, nama) VALUES (?, ?)")->execute([$id_dosen, $nama]);
    }
}

$data = $pdo->query("SELECT * FROM $entity")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kelola <?php echo ucfirst($entity); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 bg-dark text-white p-4">
                <h3 class="mb-4">Menu</h3>
                <a href="crud.php?entity=prodi" class="d-block mb-2 text-white">Kelola Prodi</a>
                <a href="crud.php?entity=kelas" class="d-block mb-2 text-white">Kelola Kelas</a>
                <a href="crud.php?entity=mata_kuliah" class="d-block mb-2 text-white">Kelola Mata Kuliah</a>
                <a href="crud.php?entity=dosen" class="d-block mb-2 text-white">Kelola Dosen</a>
                <a href="jadwal.php" class="d-block mb-2 text-white">Hasil Jadwal</a>
                <a href="logout.php" class="d-block mb-2 text-white">Logout</a>
            </div>
            <!-- Content -->
            <div class="col-md-9 p-4">
                <h2>Kelola <?php echo ucfirst($entity); ?></h2>
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($entity == 'prodi') { ?>
                            <form method="POST" class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">Nama Prodi</label>
                                    <input type="text" name="nama" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">Tambah</button>
                            </form>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $row) { ?>
                                        <tr>
                                            <td><?php echo $row['nama']; ?></td>
                                            <td>
                                                <a href="crud.php?entity=prodi&action=edit&id=<?php echo $row['id_prodi']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                <a href="crud.php?entity=prodi&action=delete&id=<?php echo $row['id_prodi']; ?>" class="btn btn-danger btn-sm">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } elseif ($entity == 'kelas') { ?>
                            <form method="POST" class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">Prodi</label>
                                    <select name="id_prodi" class="form-select" required>
                                        <?php foreach ($prodi as $p) { ?>
                                            <option value="<?php echo $p['id_prodi']; ?>"><?php echo $p['nama']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Kelas</label>
                                    <input type="text" name="nama" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Mahasiswa</label>
                                    <input type="number" name="jumlah_mahasiswa" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">Tambah</button>
                            </form>
                            <!-- Tabel kelas -->
                        <?php } elseif ($entity == 'mata_kuliah') { ?>
                            <form method="POST" class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">Prodi</label>
                                    <select name="id_prodi" class="form-select" required>
                                        <?php foreach ($prodi as $p) { ?>
                                            <option value="<?php echo $p['id_prodi']; ?>"><?php echo $p['nama']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Mata Kuliah</label>
                                    <input type="text" name="nama" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jenis</label>
                                    <select name="jenis" class="form-select" required>
                                        <option value="teori">Teori</option>
                                        <option value="praktikum">Praktikum</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Durasi (slot)</label>
                                    <input type="number" name="durasi" step="0.5" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Dosen</label>
                                    <select name="id_dosen" class="form-select" required>
                                        <?php foreach ($dosen as $d) { ?>
                                            <option value="<?php echo $d['id_dosen']; ?>"><?php echo $d['nama']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Tambah</button>
                            </form>
                            <!-- Tabel mata kuliah -->
                        <?php } elseif ($entity == 'dosen') { ?>
                            <form method="POST" class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">Nama Dosen</label>
                                    <input type="text" name="nama" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success">Tambah</button>
                            </form>
                            <!-- Tabel dosen -->
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>