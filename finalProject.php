<?php

// --- Konfigurasi Database ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "movie_release_date";

// --- Fungsi untuk Koneksi ke Database ---
function getDbConnection($servername, $username, $password, $dbname)
{
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Koneksi ke database gagal: " . $conn->connect_error);
    }
    return $conn;
}

// --- Fungsi untuk Mengambil Data Film dari Database ---
function getMoviesFromDatabase($conn): array
{
    $movies = [];
    $sql = "SELECT id, title, release_year, poster_url FROM films";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = [
                'id' => $row['id'],
                'judul' => $row['title'],
                'tahun' => $row['release_year'],
                'poster' => $row['poster_url']
            ];
        }
    }
    return $movies;
}

// Tutup koneksi database di akhir skrip
// Ini akan dilakukan setelah semua data diambil dan sebelum HTML dikirim
$conn = getDbConnection($servername, $username, $password, $dbname);
$initialMovies = getMoviesFromDatabase($conn); // Ambil data film awal
$conn->close();

// Definisikan path dasar poster
$poster_base_path = 'images/posters/';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisasi Sorting dan Searching Film</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // *** PENTING: KONFIGURASI TAILWIND HARUS DI SINI! ***
        // Ini memastikan Tailwind mengenali warna kustom Anda sebelum CSS lainnya diproses.
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        netflix_bg: '#141414',
                        netflix_card: '#222222',
                        netflix_red: '#e50914',
                        netflix_text_light: '#f5f5f5',
                        netflix_text_dark: '#aaaaaa',
                        netflix_border: '#444444',
                    }
                }
            }
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Gaya kustom untuk panah select, karena Tailwind tidak menyediakan secara langsung */
        select {
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%204%205%22%3E%3Cpath%20fill%3D%22%23f5f5f5%22%20d%3D%22M2%200L0%202h4L2%200zM2%205L0%203h4L2%205z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 0.6rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        /* Style untuk batang visualisasi */
        .bar-container {
            display: flex;
            align-items: flex-end;
            gap: 2px; /* Jarak antar batang */
            height: 200px; /* Tinggi maksimum visualisasi */
            background-color: var(--tw-colors-netflix_card); /* Menggunakan variabel CSS dari Tailwind config */
            padding: 5px;
            border-radius: 5px;
            margin-bottom: 20px;
            overflow-x: auto; /* Jika banyak batang, bisa discroll */
            border: 1px solid var(--tw-colors-netflix_border); /* Border */
        }
        .bar {
            flex-grow: 1; /* Batang akan mengisi ruang */
            min-width: 20px; /* Lebar minimum per batang */
            background-color: var(--tw-colors-netflix_red); /* Warna default merah netflix */
            transition: height 0.1s ease, background-color 0.1s ease; /* Transisi halus untuk animasi */
            display: flex;
            align-items: flex-end;
            justify-content: center;
            font-size: 0.75rem;
            color: var(--tw-colors-netflix_text_light); /* Warna teks tahun */
            font-weight: bold;
        }
        /* Menggunakan warna hex untuk warna animasi agar konsisten */
        .bar.comparing { background-color: #f7d000; } /* Kuning yang lebih gelap */
        .bar.swapping { background-color: #007bff; } /* Biru yang lebih gelap */
        .bar.sorted { background-color: #00b300; } /* Hijau yang lebih gelap */
        .bar.found { background-color: #0077c2; } /* Biru muda yang lebih gelap */
        .bar.pivot { background-color: #cc3300; } /* Oranye gelap untuk pivot */
    </style>
</head>
<body class="font-roboto bg-netflix_bg text-netflix_text_light p-5 leading-relaxed">

<div class="max-w-7xl mx-auto p-8 bg-netflix_card rounded-lg shadow-xl">
    <h1 class="text-netflix_red text-4xl font-bold text-center mb-6">Visualisasi Algoritma Sorting dan Searching Film</h1>

    <div class="bg-netflix_card p-6 rounded-lg mb-8 border border-netflix_border">
        <h2 class="text-netflix_red text-2xl font-bold mb-4">Opsi Sorting:</h2>
        <form id="sortForm" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="md:pr-4">
                <label for="sort_type" class="block mb-2 font-bold text-netflix_text_light">Pilih Algoritma Sorting:</label>
                <select name="sort_type" id="sort_type"
                        class="w-full p-3 mb-4 rounded-md border border-netflix_border bg-netflix_bg text-netflix_text_light
                                focus:outline-none focus:ring-2 focus:ring-netflix_red">
                    <option value="none">Tidak ada</option>
                    <option value="insertion">Insertion Sort</option>
                    <option value="selection">Selection Sort</option>
                </select>

                <label class="block mb-2 font-bold text-netflix_text_light">Urutan Sorting:</label>
                <div class="mb-4">
                    <input type="radio" id="order_asc" name="sort_order" value="asc"
                           class="mr-2 text-netflix_red focus:ring-netflix_red" checked>
                    <label for="order_asc">Ascending (Terlama ke Terbaru)</label>
                </div>
                <div>
                    <input type="radio" id="order_desc" name="sort_order" value="desc"
                           class="mr-2 text-netflix_red focus:ring-netflix_red">
                    <label for="order_desc">Descending (Terbaru ke Terlama)</label>
                </div>
                <label for="animation_speed_sort" class="block mb-2 font-bold text-netflix_text_light">Kecepatan Animasi (ms):</label>
                <input type="number" id="animation_speed_sort" value="100" min="10" max="1000" step="10"
                       class="w-full p-3 mb-4 rounded-md border border-netflix_border bg-netflix_bg text-netflix_text_light
                               focus:outline-none focus:ring-2 focus:ring-netflix_red">
            </div>
            <div class="md:pl-4 flex items-end">
                 <button type="submit" id="runSortButton"
                        class="w-full py-3 px-6 bg-netflix_red text-netflix_text_light font-bold rounded-md cursor-pointer
                                hover:bg-red-700 transition-colors duration-300">
                    Jalankan Sorting
                </button>
            </div>
        </form>
    </div>

    <div class="bg-netflix_card p-6 rounded-lg mb-8 border border-netflix_border">
        <h2 class="text-netflix_red text-2xl font-bold mb-4">Opsi Pencarian:</h2>
        <form id="searchForm" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="md:pr-4">
                <label for="search_keyword" class="block mb-2 font-bold text-netflix_text_light">Kata Kunci Pencarian:</label>
                <input type="text" id="search_keyword" name="search_keyword"
                       placeholder="Contoh: Batman atau 2023"
                       class="w-full p-3 mb-4 rounded-md border border-netflix_border bg-netflix_bg text-netflix_text_light
                               focus:outline-none focus:ring-2 focus:ring-netflix_red">
                <br>

                <label for="search_by" class="block mb-2 font-bold text-netflix_text_light">Cari Berdasarkan:</label>
                <select name="search_by" id="search_by"
                        class="w-full p-3 mb-4 rounded-md border border-netflix_border bg-netflix_bg text-netflix_text_light
                                focus:outline-none focus:ring-2 focus:ring-netflix_red">
                    <option value="none">Tidak ada</option>
                    <option value="title">Judul (Linear Search)</option>
                    <option value="year">Tahun Rilis (Binary Search)</option>
                </select>
                <label for="animation_speed_search" class="block mb-2 font-bold text-netflix_text_light">Kecepatan Animasi (ms):</label>
                <input type="number" id="animation_speed_search" value="100" min="10" max="1000" step="10"
                       class="w-full p-3 mb-4 rounded-md border border-netflix_border bg-netflix_bg text-netflix_text_light
                               focus:outline-none focus:ring-2 focus="ring-netflix_red">
            </div>
            <div class="md:pl-4 flex items-end">
                <button type="submit" id="runSearchButton"
                       class="w-full py-3 px-6 bg-netflix_red text-netflix_text_light font-bold rounded-md cursor-pointer
                               hover:bg-red-700 transition-colors duration-300">
                   Jalankan Pencarian
               </button>
            </div>
        </form>
    </div>

    <button type="button" id="resetButton"
            class="py-3 px-6 bg-netflix_text_dark text-netflix_text_light font-bold rounded-md cursor-pointer
                   hover:bg-gray-700 transition-colors duration-300 mx-auto w-fit block mb-8">
        Reset Data Awal
    </button>

    <hr class="border-t border-netflix_border my-8">

    <h2 class="text-netflix_red text-2xl font-bold text-center mb-4">Visualisasi Algoritma</h2>
    <div id="visualizer-container" class="bar-container">
        </div>
    <div id="metrics-display" class="bg-netflix_card p-4 rounded-lg mb-6 text-netflix_text_light border border-netflix_border hidden">
        </div>

    <h2 class="text-netflix_red text-2xl font-bold text-center mb-6">Daftar Film</h2>
    <div id="movies-list-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        </div>
</div>

<script>
    // Data film awal yang diambil dari PHP dan di-encode ke JSON
    const initialMoviesData = <?php echo json_encode($initialMovies); ?>;
    const POSTER_BASE_PATH = '<?php echo $poster_base_path; ?>';

    let currentMovies = [...initialMoviesData]; // Salinan data yang bisa diubah untuk visualisasi
    // Hitung maxYear dan minYear dari data awal untuk scaling
    let maxYear = 0;
    let minYear = Infinity;
    if (initialMoviesData.length > 0) {
        maxYear = Math.max(...initialMoviesData.map(movie => movie.tahun));
        minYear = Math.min(...initialMoviesData.map(movie => movie.tahun));
    }
    
    let animationSpeed = 100; // Default speed in ms

    const visualizerContainer = document.getElementById('visualizer-container');
    const moviesListContainer = document.getElementById('movies-list-container');
    const metricsDisplay = document.getElementById('metrics-display');

    // Dapatkan referensi form dan tombol
    const sortForm = document.getElementById('sortForm');
    const searchForm = document.getElementById('searchForm');
    const resetButton = document.getElementById('resetButton');

    const sortTypeSelect = document.getElementById('sort_type');
    const searchBySelect = document.getElementById('search_by');
    const searchKeywordInput = document.getElementById('search_keyword');
    const animationSpeedSortInput = document.getElementById('animation_speed_sort');
    const animationSpeedSearchInput = document.getElementById('animation_speed_search');


    // --- Fungsi Bantuan untuk Visualisasi ---
    function renderBars(arr, activeIndices = [], comparisonIndices = [], foundIndices = [], sortedIndices = []) {
        visualizerContainer.innerHTML = '';
        // Sesuaikan rentang jika minYear dan maxYear sama (misal hanya 1 film atau semua tahun sama)
        const range = maxYear - minYear === 0 ? 1 : maxYear - minYear;

        arr.forEach((movie, index) => {
            const bar = document.createElement('div');
            bar.classList.add('bar', 'relative', 'overflow-hidden'); 
            
            // Calculate height based on year, scaled to container height (200px)
            // Add a base height (e.g., 20px) to ensure even the smallest bar is visible
            const scaledHeight = ((movie.tahun - minYear) / range) * 180 + 20; 
            bar.style.height = `${scaledHeight}px`;
            
            // Add year text inside the bar, ensure it's visible
            const yearText = document.createElement('span');
            yearText.textContent = movie.tahun;
            yearText.classList.add('absolute', 'bottom-0', 'py-1', 'px-0.5', 'text-xs', 'bg-black/50', 'text-netflix_text_light', 'rounded-t-sm', 'w-full', 'text-center');
            bar.appendChild(yearText);

            if (sortedIndices.includes(index)) {
                bar.classList.add('sorted');
            } else if (foundIndices.includes(index)) {
                bar.classList.add('found');
            } else if (activeIndices.includes(index)) {
                bar.classList.add('swapping'); 
            } else if (comparisonIndices.includes(index)) {
                bar.classList.add('comparing'); 
            } else {
                bar.style.backgroundColor = ''; 
                bar.classList.remove('swapping', 'comparing', 'found', 'sorted');
            }

            visualizerContainer.appendChild(bar);
        });
    }

    function renderMoviesList(arr) {
        moviesListContainer.innerHTML = '';
        if (arr.length === 0) {
            moviesListContainer.innerHTML = "<p class='text-netflix_red font-bold text-center text-lg mt-5 col-span-full'>Tidak ada film yang cocok.</p>";
            return;
        }

        arr.forEach(movie => {
            const movieCard = document.createElement('div');
            movieCard.classList.add('bg-netflix_bg', 'rounded-lg', 'p-2', 'text-center', 'shadow-lg', 'transform', 'hover:scale-105',
                'transition-transform', 'duration-200', 'cursor-pointer', 'flex', 'flex-col', 'items-center', 'border', 'border-netflix_border');

            let posterHtml;
            if (movie.poster) {
                posterHtml = `<img src="${POSTER_BASE_PATH}${movie.poster}" alt="${movie.judul} Poster"
                                    class="w-full h-auto object-cover rounded-md mb-2 aspect-[2/3]">`;
            } else {
                posterHtml = `<div class="w-full h-48 bg-netflix_card flex items-center justify-center text-netflix_text_dark text-xs rounded-md mb-2 aspect-[2/3]">
                                    No Poster Available
                                </div>`;
            }

            movieCard.innerHTML = `
                ${posterHtml}
                <h3 class="text-netflix_text_light text-sm font-semibold mb-1">${movie.judul}</h3>
                <p class="text-netflix_text_dark text-xs">(${movie.tahun})</p>
            `;
            moviesListContainer.appendChild(movieCard);
        });
    }

    function displayMetrics(comparisons, swaps, executionTime, type) {
        metricsDisplay.innerHTML = '';
        metricsDisplay.classList.remove('hidden'); 

        if (type === 'sorting') {
            metricsDisplay.innerHTML = `
                <p>Total Perbandingan: <strong>${comparisons.toLocaleString()}</strong></p>
                <p>Total Pertukaran/Pergeseran: <strong>${swaps.toLocaleString()}</strong></p>
                <p>Waktu Eksekusi Sorting: <strong>${executionTime.toFixed(2)}</strong> mikrodetik</p>
            `;
        } else if (type === 'searching') {
             metricsDisplay.innerHTML = `
                <p>Waktu Eksekusi Pencarian: <strong>${executionTime.toFixed(2)}</strong> mikrodetik</p>
            `;
        }
    }

    function hideMetrics() {
        metricsDisplay.classList.add('hidden');
    }

    // --- Algoritma Sorting (Diimplementasikan di JavaScript) ---
    async function insertionSortJS(arr, order = 'asc') {
        let n = arr.length;
        let comparisons = 0;
        let swaps = 0;
        const startTime = performance.now();

        for (let i = 1; i < n; i++) {
            let key = arr[i];
            let j = i - 1;

            renderBars(arr, [i], [], [], Array.from({length: i}, (_, k) => k)); 
            await new Promise(resolve => setTimeout(resolve, animationSpeed));

            while (j >= 0) {
                comparisons++;
                renderBars(arr, [j + 1], [j], [], Array.from({length: i}, (_, k) => k)); 
                await new Promise(resolve => setTimeout(resolve, animationSpeed));

                const comparison = (order === 'asc') ? (arr[j].tahun > key.tahun) : (arr[j].tahun < key.tahun);
                if (comparison) {
                    arr[j + 1] = arr[j];
                    j = j - 1;
                    swaps++; 
                } else {
                    break;
                }
            }
            arr[j + 1] = key;
            renderBars(arr, [], [], [], Array.from({length: i + 1}, (_, k) => k)); 
            await new Promise(resolve => setTimeout(resolve, animationSpeed));
        }

        const endTime = performance.now();
        const executionTime = (endTime - startTime) * 1000;

        renderBars(arr, [], [], [], Array.from({length: n}, (_, k) => k)); 
        await new Promise(resolve => setTimeout(resolve, animationSpeed * 2));
        renderBars(arr); 

        return { sorted_movies: arr, comparisons, swaps, execution_time: executionTime };
    }

    async function selectionSortJS(arr, order = 'asc') {
        let n = arr.length;
        let comparisons = 0;
        let swaps = 0;
        const startTime = performance.now();

        for (let i = 0; i < n - 1; i++) {
            let selectedIndex = i;

            renderBars(arr, [selectedIndex], [], [], Array.from({length: i}, (_, k) => k)); 
            await new Promise(resolve => setTimeout(resolve, animationSpeed));

            for (let j = i + 1; j < n; j++) {
                comparisons++;
                renderBars(arr, [selectedIndex], [j], [], Array.from({length: i}, (_, k) => k)); 
                await new Promise(resolve => setTimeout(resolve, animationSpeed));

                const comparison = (order === 'asc') ? (arr[j].tahun < arr[selectedIndex].tahun) : (arr[j].tahun > arr[selectedIndex].tahun);
                if (comparison) {
                    selectedIndex = j;
                }
            }

            if (selectedIndex !== i) {
                renderBars(arr, [i, selectedIndex], [], [], Array.from({length: i}, (_, k) => k)); 
                await new Promise(resolve => setTimeout(resolve, animationSpeed));

                let temp = arr[i];
                arr[i] = arr[selectedIndex];
                arr[selectedIndex] = temp;
                swaps++;

                renderBars(arr, [], [], [], Array.from({length: i + 1}, (_, k) => k)); 
                await new Promise(resolve => setTimeout(resolve, animationSpeed));
            } else {
                renderBars(arr, [], [], [], Array.from({length: i + 1}, (_, k) => k)); 
                await new Promise(resolve => setTimeout(resolve, animationSpeed));
            }
        }

        const endTime = performance.now();
        const executionTime = (endTime - startTime) * 1000;

        renderBars(arr, [], [], [], Array.from({length: n}, (_, k) => k)); 
        await new Promise(resolve => setTimeout(resolve, animationSpeed * 2));
        renderBars(arr); 

        return { sorted_movies: arr, comparisons, swaps, execution_time: executionTime };
    }

    // --- Algoritma Searching (Diimplementasikan di JavaScript) ---
    async function linearSearchJS(arr, keyword) {
        let foundMovies = [];
        const lowerKeyword = keyword.toLowerCase();
        const startTime = performance.now();

        for (let i = 0; i < arr.length; i++) {
            renderBars(arr, [], [i], []); 
            await new Promise(resolve => setTimeout(resolve, animationSpeed));

            if (arr[i].judul.toLowerCase().includes(lowerKeyword)) {
                foundMovies.push(arr[i]);
                renderBars(arr, [], [], [i]); 
                await new Promise(resolve => setTimeout(resolve, animationSpeed * 2)); 
            }
        }
        const endTime = performance.now();
        const executionTime = (endTime - startTime) * 1000;

        renderBars(arr); 
        return { found_movies: foundMovies, execution_time: executionTime };
    }

    async function binarySearchJS(arr, targetYear) {
        // Binary search memerlukan array terurut.
        // Kita akan mengurutkan dulu secara ascending berdasarkan tahun.
        // Pastikan pengurutan ini memperlakukan 'tahun' sebagai angka.
        const tempSortedArr = [...arr].sort((a, b) => {
            return parseInt(a.tahun) - parseInt(b.tahun);
        });
        
        let foundMovies = [];
        let low = 0;
        let high = tempSortedArr.length - 1;
        const startTime = performance.now();

        let initialMidFoundIndex = -1; // Untuk menyimpan indeks mid pertama kali tahun target ditemukan

        // Fase 1: Cari salah satu kemunculan targetYear menggunakan binary search
        while (low <= high) {
            let mid = Math.floor((low + high) / 2);
            let midYear = parseInt(tempSortedArr[mid].tahun); // Pastikan tahun adalah integer untuk perbandingan

            renderBars(tempSortedArr, [mid], [low, high], []); 
            await new Promise(resolve => setTimeout(resolve, animationSpeed));

            if (midYear === targetYear) {
                initialMidFoundIndex = mid; // Simpan indeks ini
                break; // Hentikan pencarian utama, kita akan perluas dari sini
            } else if (midYear < targetYear) {
                low = mid + 1;
            } else {
                high = mid - 1;
            }
        }

        // Fase 2: Jika targetYear ditemukan (yaitu initialMidFoundIndex valid), kumpulkan semua elemen dengan tahun yang sama
        if (initialMidFoundIndex !== -1) {
            let foundIndices = [];

            // Kumpulkan semua film ke kiri dari initialMidFoundIndex yang memiliki tahun yang sama
            let i = initialMidFoundIndex;
            while (i >= 0 && parseInt(tempSortedArr[i].tahun) === targetYear) {
                foundMovies.unshift(tempSortedArr[i]); // Tambahkan ke awal agar urutan relatif terjaga
                foundIndices.unshift(i); // Tambahkan indeks ke awal untuk visualisasi
                i--;
            }

            // Kumpulkan semua film ke kanan dari (setelah) initialMidFoundIndex yang memiliki tahun yang sama
            // Dimulai dari initialMidFoundIndex + 1 karena initialMidFoundIndex sudah ditambahkan di loop atas
            let j = initialMidFoundIndex + 1;
            while (j < tempSortedArr.length && parseInt(tempSortedArr[j].tahun) === targetYear) {
                foundMovies.push(tempSortedArr[j]);
                foundIndices.push(j);
                j++;
            }

            // Visualisasi semua elemen yang ditemukan sekaligus
            renderBars(tempSortedArr, [], [], foundIndices); 
            await new Promise(resolve => setTimeout(resolve, animationSpeed * 3));
        }
        
        const endTime = performance.now();
        const executionTime = (endTime - startTime) * 1000;

        // Penting: Setelah pencarian selesai, kembalikan visualisasi ke kondisi dasar
        renderBars(tempSortedArr); 
        return { found_movies: foundMovies, execution_time: executionTime };
    }


    // --- Event Listener dan Logika Utama ---

    // Handler untuk form Sorting
    sortForm.addEventListener('submit', async (event) => {
        event.preventDefault(); 
        hideMetrics(); 
        renderBars(currentMovies); // Reset visualisasi sebelum sorting

        const sortType = sortTypeSelect.value;
        const sortOrder = document.querySelector('#sortForm input[name="sort_order"]:checked').value;
        animationSpeed = parseInt(animationSpeedSortInput.value); // Gunakan kecepatan dari form sorting

        if (sortType === 'none') {
            alert('Silakan pilih algoritma sorting.');
            return;
        }

        let sortResults;
        const tempArrayForSort = [...currentMovies]; 

        if (sortType === 'insertion') {
            sortResults = await insertionSortJS(tempArrayForSort, sortOrder);
        } else if (sortType === 'selection') {
            sortResults = await selectionSortJS(tempArrayForSort, sortOrder);
        }
        currentMovies = sortResults.sorted_movies; 
        displayMetrics(sortResults.comparisons, sortResults.swaps, sortResults.execution_time, 'sorting');
        renderMoviesList(currentMovies); 
    });

    // Handler untuk form Searching
    searchForm.addEventListener('submit', async (event) => {
        event.preventDefault(); 
        hideMetrics(); 
        renderBars(currentMovies); // Reset visualisasi sebelum searching (kembali ke keadaan saat ini)

        const searchKeyword = searchKeywordInput.value.trim();
        const searchBy = searchBySelect.value;
        animationSpeed = parseInt(animationSpeedSearchInput.value); // Gunakan kecepatan dari form searching

        if (searchBy === 'none' || !searchKeyword) {
            alert('Silakan pilih metode pencarian dan masukkan kata kunci.');
            return;
        }

        let searchResults;
        let targetMovies = [...currentMovies]; // Gunakan data saat ini untuk pencarian

        if (searchBy === 'title') {
            searchResults = await linearSearchJS(targetMovies, searchKeyword);
        } else if (searchBy === 'year') {
             // Untuk binary search, pastikan array sudah terurut berdasarkan tahun
             // PENTING: Untuk binary search, kita akan mengurutkan *currentMovies* secara non-visual sebelum pencarian.
             // Ini karena binary search MENGHARUSKAN array terurut.
            targetMovies.sort((a,b) => a.tahun - b.tahun); 
            searchResults = await binarySearchJS(targetMovies, parseInt(searchKeyword));
        }
        displayMetrics(0, 0, searchResults.execution_time, 'searching');
        renderMoviesList(searchResults.found_movies); 
    });


    // Handler untuk tombol Reset Data Awal
    resetButton.addEventListener('click', () => {
        currentMovies = [...initialMoviesData]; // Reset ke data awal
        maxYear = 0; // Recalculate max/min year on reset
        minYear = Infinity;
        if (currentMovies.length > 0) {
            maxYear = Math.max(...currentMovies.map(movie => movie.tahun));
            minYear = Math.min(...currentMovies.map(movie => movie.tahun));
        }

        renderBars(currentMovies); 
        renderMoviesList(currentMovies); 
        hideMetrics(); 
        sortForm.reset(); // Reset form sorting
        searchForm.reset(); // Reset form searching
        document.getElementById('sort_type').value = 'none'; 
        document.getElementById('search_by').value = 'none'; 
        document.getElementById('order_asc').checked = true; 
        animationSpeedSortInput.value = 100; // Reset kecepatan
        animationSpeedSearchInput.value = 100; // Reset kecepatan
    });

    // Initial render
    renderBars(currentMovies);
    renderMoviesList(currentMovies);

</script>

</body>
</html>