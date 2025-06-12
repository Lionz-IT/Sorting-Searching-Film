<?php

// --- Konfigurasi Database ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "movie_release_date";

// --- Fungsi untuk Koneksi ke Database ---
function getDbConnection($servername, $username, $password, $dbname)
{
    // Buat koneksi
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Cek koneksi
    if ($conn->connect_error) {
        die("Koneksi ke database gagal: " . $conn->connect_error);
    }
    return $conn;
}

// --- Fungsi untuk Mengambil Data Film dari Database ---
function getMoviesFromDatabase($conn): array
{
    $movies = [];
    // Pastikan ORDER BY agar urutan awal konsisten untuk visualisasi
    $sql = "SELECT id, title, release_year FROM films ORDER BY id ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = [
                'id' => $row['id'],
                'judul' => $row['title'],
                'tahun' => $row['release_year']
            ];
        }
    }
    return $movies;
}

/**
 * Fungsi Pembantu untuk Menampilkan Keadaan Array dalam Visualisasi HTML.
 * @param array $arr Array film.
 * @param string $title Judul untuk tampilan keadaan array.
 * @param array $highlights Array asosiatif untuk indeks yang perlu disorot dan jenis sorotan.
 * Contoh: ['index_type' => index_value]
 * @param array $highlightColors Array asosiatif warna untuk jenis sorotan.
 * Contoh: ['index_type' => 'color_code']
 */
function displayArrayState(array $arr, string $title, array $highlights = [], array $highlightColors = []): void
{
    echo "<div><h4>{$title}</h4>";
    echo "<div style='display: flex; gap: 5px; margin-bottom: 10px; flex-wrap: wrap;'>"; // Tambah flex-wrap
    foreach ($arr as $index => $movie) {
        $style = "border: 1px solid #ccc; padding: 5px; min-width: 60px; text-align: center; background-color: #f0f0f0; border-radius: 4px;";
        $highlighted = false;

        foreach ($highlights as $type => $highlightIndex) {
            if ($highlightIndex === $index) {
                $color = $highlightColors[$type] ?? 'yellow'; // Default yellow
                $style .= " background-color: {$color}; border: 2px solid darkred;";
                $highlighted = true;
                break; // Hentikan jika sudah disorot oleh satu tipe
            }
        }
        
        echo "<div style='{$style}'>";
        echo "<span style='font-size: 0.7em; color: #666;'>[{$index}]</span><br>";
        echo "<strong>{$movie['tahun']}</strong><br>";
        // Tampilkan beberapa karakter judul untuk identifikasi
        echo "<span style='font-size: 0.6em; color: #888;'>" . htmlspecialchars(substr($movie['judul'], 0, 7)) . "...</span>";
        echo "</div>";
    }
    echo "</div></div>";
}

/**
 * Mengurutkan array film menggunakan Insertion Sort berdasarkan tahun rilis.
 *
 * @param array $arr Array film.
 * @param string $order 'asc' untuk ascending, 'desc' untuk descending.
 * @return array Array film yang sudah diurutkan.
 */
function insertionSortMovies(array $arr, string $order = 'asc'): array
{
    $n = count($arr);
    echo "<h3>Visualisasi Insertion Sort ({$order}):</h3>";
    displayArrayState($arr, "Array Awal");
    echo "<hr>";

    for ($i = 1; $i < $n; $i++) {
        $key = $arr[$i];
        $j = $i - 1;
        
        $highlightColors = [
            'i' => 'lightblue', // Elemen yang sedang dipertimbangkan (key)
            'j' => 'lightgreen', // Elemen yang dibandingkan dengan key
            'moved' => 'orange', // Elemen yang digeser
            'inserted' => 'pink' // Posisi key disisipkan
        ];

        echo "<p>Iterasi Luar (i=$i): Memproses elemen {$key['judul']} ({$key['tahun']})</p>";
        displayArrayState($arr, "Ambil key: A[$i]", ['i' => $i], $highlightColors);

        if ($order === 'asc') {
            while ($j >= 0 && $arr[$j]['tahun'] > $key['tahun']) {
                echo "<p>  Membandingkan A[{$j}] ({$arr[$j]['tahun']}) > Key ({$key['tahun']}). Menggeser A[{$j}] ke A[{$j}+1].</p>";
                displayArrayState($arr, "Geser A[$j]", ['j' => $j, 'moved' => $j + 1], $highlightColors);
                $arr[$j + 1] = $arr[$j];
                $j = $j - 1;
            }
        } else { // descending
            while ($j >= 0 && $arr[$j]['tahun'] < $key['tahun']) {
                echo "<p>  Membandingkan A[{$j}] ({$arr[$j]['tahun']}) < Key ({$key['tahun']}). Menggeser A[{$j}] ke A[{$j}+1].</p>";
                displayArrayState($arr, "Geser A[$j]", ['j' => $j, 'moved' => $j + 1], $highlightColors);
                $arr[$j + 1] = $arr[$j];
                $j = $j - 1;
            }
        }
        $arr[$j + 1] = $key;
        echo "<p>  Menyisipkan Key ({$key['judul']}, {$key['tahun']}) ke posisi {$j}+1.</p>";
        displayArrayState($arr, "Key Disisipkan di A[" . ($j + 1) . "]", ['inserted' => $j + 1], $highlightColors);
        echo "<hr>";
    }
    return $arr;
}

/**
 * Mengurutkan array film menggunakan Selection Sort berdasarkan tahun rilis.
 *
 * @param array $arr Array film.
 * @param string $order 'asc' untuk ascending, 'desc' untuk descending.
 * @return array Array film yang sudah diurutkan.
 */
function selectionSortMovies(array $arr, string $order = 'asc'): array
{
    $n = count($arr);
    echo "<h3>Visualisasi Selection Sort ({$order}):</h3>";
    displayArrayState($arr, "Array Awal");
    echo "<hr>";

    for ($i = 0; $i < $n - 1; $i++) {
        $selectedIndex = $i;
        $highlightColors = [
            'i' => 'lightblue', // Posisi elemen yang sedang diisi
            'j' => 'lightgreen', // Elemen yang sedang dibandingkan
            'selected' => 'orange', // Elemen yang dianggap paling ekstrem
            'swap_target' => 'pink' // Elemen yang akan ditukar
        ];

        echo "<p>Iterasi Luar (i=$i): Mencari elemen " . (($order === 'asc') ? 'terkecil' : 'terbesar') . " dari indeks $i sampai " . ($n - 1) . "</p>";
        displayArrayState($arr, "Mulai Iterasi i=$i", ['i' => $i, 'selected' => $selectedIndex], $highlightColors);

        for ($j = $i + 1; $j < $n; $j++) {
            $comparison = ($order === 'asc') ? ($arr[$j]['tahun'] < $arr[$selectedIndex]['tahun']) : ($arr[$j]['tahun'] > $arr[$selectedIndex]['tahun']);
            
            displayArrayState($arr, "Membandingkan A[{$j}] ({$arr[$j]['tahun']}) dengan A[{$selectedIndex}] ({$arr[$selectedIndex]['tahun']})", [
                'i' => $i,
                'j' => $j,
                'selected' => $selectedIndex
            ], $highlightColors);

            if ($comparison) {
                $selectedIndex = $j;
                echo "<p>      => selectedIndex diperbarui ke $j (Tahun: {$arr[$selectedIndex]['tahun']})</p>";
                displayArrayState($arr, "selectedIndex diperbarui ke $j", [
                    'i' => $i,
                    'j' => $j,
                    'selected' => $selectedIndex
                ], $highlightColors);
            }
        }

        if ($selectedIndex != $i) {
            echo "<p>  Melakukan Swap: Elemen di indeks {$i} ({$arr[$i]['judul']}, {$arr[$i]['tahun']}) dengan elemen di indeks {$selectedIndex} ({$arr[$selectedIndex]['judul']}, {$arr[$selectedIndex]['tahun']})</p>";
            displayArrayState($arr, "Sebelum Swap", ['i' => $i, 'selected' => $selectedIndex, 'swap_target' => $i, 'j' => $selectedIndex], $highlightColors);

            $temp = $arr[$i];
            $arr[$i] = $arr[$selectedIndex];
            $arr[$selectedIndex] = $temp;

            displayArrayState($arr, "Setelah Swap", ['i' => $i], $highlightColors); // Tandai elemen yang sudah diurutkan di posisi i
        } else {
            echo "<p>  Tidak ada swap karena elemen sudah di posisi yang benar untuk iterasi i=$i.</p>";
            displayArrayState($arr, "Setelah Iterasi i=$i", ['i' => $i], $highlightColors);
        }
        echo "<hr>";
    }
    return $arr;
}

/**
 * Melakukan pencarian biner pada array film yang sudah diurutkan
 * berdasarkan tahun rilis.
 *
 * Catatan: Binary Search hanya efisien jika array diurutkan berdasarkan kriteria pencarian.
 *
 * @param array $arr Array film yang sudah diurutkan.
 * @param int $targetValue Tahun rilis yang dicari.
 * @return array Array film yang ditemukan (bisa lebih dari satu jika ada tahun yang sama).
 */
function binarySearchMovieByYear(array $arr, int $targetValue): array
{
    $foundMovies = [];
    $low = 0;
    $high = count($arr) - 1;

    echo "<h3>Visualisasi Binary Search (Target Tahun: {$targetValue}):</h3>";
    displayArrayState($arr, "Array yang dicari", [], []); // Tampilkan array awal tanpa highlight
    echo "<hr>";

    $highlightColors = [
        'low' => '#e6ffe6', // Hijau muda
        'high' => '#ffe6e6', // Merah muda
        'mid' => '#e6e6ff',  // Biru muda
        'found_exact' => '#90EE90', // LightGreen
        'found_range' => '#ADD8E6' // LightBlue
    ];

    while ($low <= $high) {
        $mid = floor(($low + $high) / 2);
        $midYear = $arr[$mid]['tahun'];

        echo "<p>Low: $low, High: $high, Mid: $mid (Film: {$arr[$mid]['judul']}, Tahun: {$midYear})</p>";
        displayArrayState($arr, "Langkah Pencarian", [
            'low' => $low,
            'high' => $high,
            'mid' => $mid
        ], $highlightColors);

        if ($midYear === $targetValue) {
            echo "<p style='color: green; font-weight: bold;'>TARGET DITEMUKAN di indeks {$mid}!</p>";
            // Jika ditemukan, kita perlu mencari semua kemunculan tahun tersebut
            // di sekitar indeks tengah.
            $k = $mid;
            while ($k >= 0 && $arr[$k]['tahun'] === $targetValue) {
                array_unshift($foundMovies, $arr[$k]);
                $k--;
            }
            $k = $mid + 1;
            while ($k < count($arr) && $arr[$k]['tahun'] === $targetValue) {
                $foundMovies[] = $arr[$k];
                $k++;
            }
            // Visualisasi hasil akhir
            $finalHighlights = [];
            foreach($foundMovies as $fMovie) {
                // Cari indeks film yang ditemukan untuk highlight
                foreach($arr as $idx => $origMovie) {
                    if ($origMovie['id'] === $fMovie['id']) { // Asumsi ID unik
                        $finalHighlights['found_range_' . $idx] = $idx;
                    }
                }
            }
            displayArrayState($arr, "Hasil Ditemukan (Rentang Tahun {$targetValue})", $finalHighlights, ['found_range_' => '#90EE90']);

            echo "<p>Semua film dengan tahun {$targetValue} yang ditemukan:</p>";
            echo "<ul style='list-style-type: disc; margin-left: 20px;'>";
            foreach($foundMovies as $film) {
                echo "<li>{$film['judul']} ({$film['tahun']})</li>";
            }
            echo "</ul>";
            echo "<hr>";
            return $foundMovies;
        } elseif ($midYear < $targetValue) {
            echo "<p>Tahun di Mid ({$midYear}) < Target ({$targetValue}). Pindah ke KANAN.</p>";
            $low = $mid + 1;
        } else {
            echo "<p>Tahun di Mid ({$midYear}) > Target ({$targetValue}). Pindah ke KIRI.</p>";
            $high = $mid - 1;
        }
        echo "<hr>";
    }
    echo "<p style='color: red; font-weight: bold;'>TARGET TIDAK DITEMUKAN.</p>";
    return $foundMovies; // Akan kosong jika tidak ditemukan
}

/**
 * Melakukan pencarian linear pada array film berdasarkan judul.
 *
 * @param array $arr Array film.
 * @param string $targetTitle Judul film yang dicari (case-insensitive, partial match).
 * @return array Array film yang ditemukan.
 */
function searchMovieByTitle(array $arr, string $targetTitle): array
{
    $foundMovies = [];
    $targetTitleLower = strtolower($targetTitle); // Untuk pencarian case-insensitive

    echo "<h3>Pencarian Linear Berdasarkan Judul (Kueri: '{$targetTitle}'):</h3>";
    displayArrayState($arr, "Array yang dicari", [], []);
    echo "<hr>";

    $highlightColors = ['match' => 'lime', 'no_match' => 'grey'];
    foreach ($arr as $index => $movie) {
        $isMatch = strpos(strtolower($movie['judul']), $targetTitleLower) !== false;
        
        displayArrayState($arr, "Memeriksa A[{$index}] ({$movie['judul']})", [
            'match' => $index // Jika cocok, akan di-highlight hijau
        ], ['match' => ($isMatch ? 'lime' : 'lightgray')]); // Warna berbeda untuk cocok/tidak

        if ($isMatch) {
            echo "<p>  MATCH: Film '{$movie['judul']}' cocok dengan kueri.</p>";
            $foundMovies[] = $movie;
        } else {
            echo "<p>  NO MATCH: Film '{$movie['judul']}' tidak cocok.</p>";
        }
        echo "<hr>";
    }
    if (empty($foundMovies)) {
        echo "<p style='color: red; font-weight: bold;'>Tidak ada film ditemukan dengan judul yang mengandung '{$targetTitle}'.</p>";
    } else {
        echo "<p style='color: green; font-weight: bold;'>Ditemukan " . count($foundMovies) . " film dengan judul yang mengandung '{$targetTitle}'.</p>";
    }
    return $foundMovies;
}

// --- Alur Utama Program ---

// 1. Buat koneksi ke database
$conn = getDbConnection($servername, $username, $password, $dbname);

// 2. Ambil data film dari database
$movies = getMoviesFromDatabase($conn);

// Inisialisasi variabel untuk hasil
$displayedMovies = $movies; // Default: tampilkan film asli
$message = "";
$showVisualizations = false; // Kontrol apakah visualisasi ditampilkan

// 3. Tangani input dari formulir
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $showVisualizations = true; // Aktifkan visualisasi saat ada POST request

    // Tangani Sort
    if (isset($_POST['sort_type']) && isset($_POST['sort_order'])) {
        $sortType = $_POST['sort_type'];
        $sortOrder = $_POST['sort_order'];

        $moviesToSort = $movies; // Kloning array untuk sorting
        
        echo "<h2>Melakukan Pengurutan...</h2>";
        if ($sortType === 'insertion_sort') {
            $displayedMovies = insertionSortMovies($moviesToSort, $sortOrder);
            $message = "Film diurutkan menggunakan Insertion Sort (" . ucfirst($sortOrder) . ").";
        } elseif ($sortType === 'selection_sort') {
            $displayedMovies = selectionSortMovies($moviesToSort, $sortOrder);
            $message = "Film diurutkan menggunakan Selection Sort (" . ucfirst($sortOrder) . ").";
        }
    }

    // Tangani Search
    if (isset($_POST['search_query']) && !empty($_POST['search_query']) && isset($_POST['search_by'])) {
        $searchQuery = $_POST['search_query'];
        $searchBy = $_POST['search_by'];
        
        echo "<h2>Melakukan Pencarian...</h2>";
        if ($searchBy === 'year') {
            if (is_numeric($searchQuery)) {
                $targetYear = (int)$searchQuery;
                $sortedForSearch = insertionSortMovies($movies, 'asc'); // Urutkan dulu untuk binary search
                $displayedMovies = binarySearchMovieByYear($sortedForSearch, $targetYear);
                $message = "Hasil pencarian untuk tahun: {$targetYear}.";
            } else {
                $message = "Input tahun tidak valid. Harap masukkan angka.";
                $displayedMovies = []; // Kosongkan tampilan jika input salah
            }
        } elseif ($searchBy === 'title') {
            $displayedMovies = searchMovieByTitle($movies, $searchQuery); // Pencarian linear
            $message = "Hasil pencarian untuk judul: '{$searchQuery}'.";
        }
    }
}

// Tutup koneksi database
$conn->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Film</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 1000px; /* Lebarkan sedikit container */
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2, h3, h4 {
            color: #0056b3;
            border-bottom: 1px solid #eee; /* Kurangi ketebalan border */
            padding-bottom: 8px;
            margin-top: 20px;
        }
        hr {
            border: none;
            border-top: 1px dashed #ddd;
            margin: 20px 0;
        }
        form {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        form select, form input[type="text"], form input[type="submit"] {
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: calc(100% - 16px);
            box-sizing: border-box;
        }
        form input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            width: auto;
            padding: 8px 15px;
            margin-right: 10px;
        }
        form input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .film-list {
            list-style: none;
            padding: 0;
        }
        .film-item {
            background: #fdfdfd;
            border: 1px solid #ddd;
            margin-bottom: 8px;
            padding: 10px;
            border-radius: 5px;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .flex-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .flex-item {
            flex: 1;
            min-width: 250px;
        }
        pre {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            white-space: pre-wrap; /* Agar teks wrap */
            word-break: break-all;
            font-size: 0.9em;
        }
        /* Style untuk visualisasi array */
        .array-container {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .array-element {
            border: 1px solid #ccc;
            padding: 5px;
            min-width: 60px;
            text-align: center;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
        .array-element strong {
            display: block;
        }
        .array-element span {
            font-size: 0.7em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manajemen Film: Sorting dan Pencarian Interaktif</h1>

        <div class="flex-container">
            <div class="flex-item">
                <h2>Opsi Pengurutan</h2>
                <form action="" method="post">
                    <label for="sort_type">Pilih Algoritma Sort:</label>
                    <select name="sort_type" id="sort_type">
                        <option value="insertion_sort" <?php echo (isset($_POST['sort_type']) && $_POST['sort_type'] == 'insertion_sort') ? 'selected' : ''; ?>>Insertion Sort</option>
                        <option value="selection_sort" <?php echo (isset($_POST['sort_type']) && $_POST['sort_type'] == 'selection_sort') ? 'selected' : ''; ?>>Selection Sort</option>
                    </select>

                    <label for="sort_order">Urutkan Secara:</label>
                    <select name="sort_order" id="sort_order">
                        <option value="asc" <?php echo (isset($_POST['sort_order']) && $_POST['sort_order'] == 'asc') ? 'selected' : ''; ?>>Ascending (Tahun Terlama ke Terbaru)</option>
                        <option value="desc" <?php echo (isset($_POST['sort_order']) && $_POST['sort_order'] == 'desc') ? 'selected' : ''; ?>>Descending (Tahun Terbaru ke Terlama)</option>
                    </select>

                    <input type="submit" value="Urutkan Film">
                </form>
            </div>

            <div class="flex-item">
                <h2>Opsi Pencarian</h2>
                <form action="" method="post">
                    <label for="search_query">Kueri Pencarian:</label>
                    <input type="text" name="search_query" id="search_query" placeholder="Judul atau Tahun Rilis" value="<?php echo htmlspecialchars($_POST['search_query'] ?? ''); ?>">

                    <label for="search_by">Cari Berdasarkan:</label>
                    <select name="search_by" id="search_by">
                        <option value="title" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'title') ? 'selected' : ''; ?>>Judul</option>
                        <option value="year" <?php echo (isset($_POST['search_by']) && $_POST['search_by'] == 'year') ? 'selected' : ''; ?>>Tahun Rilis</option>
                    </select>

                    <input type="submit" value="Cari Film">
                </form>
            </div>
        </div>

        <?php if (!empty($message)) : ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if ($showVisualizations) : ?>
            <div id="visualizations">
                <h2 style="color: #FF5733;">Detail Visualisasi Algoritma</h2>
                </div>
            <hr>
        <?php endif; ?>

        <h2>Hasil Akhir:</h2>
        <?php if (empty($displayedMovies)) : ?>
            <p class="error-message">Tidak ada film yang ditemukan sesuai kriteria.</p>
        <?php else : ?>
            <ul class="film-list">
                <?php foreach ($displayedMovies as $movie) : ?>
                    <li class="film-item">
                        <strong><?php echo htmlspecialchars($movie['judul']); ?></strong> (<?php echo htmlspecialchars($movie['tahun']); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>