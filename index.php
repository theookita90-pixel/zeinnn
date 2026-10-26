
<?php
require_once "config.php";

function balasanChatbot($pesan, $kategori, $rating) {
    $teks = strtolower($pesan);

    if (
        strpos($teks, "bully") !== false ||
        strpos($teks, "perundungan") !== false ||
        strpos($teks, "kekerasan") !== false
    ) {
        return "Terima kasih telah menyampaikan hal ini. Masalah perundungan dan kekerasan perlu ditangani dengan serius. Demi keamanan, sampaikan detail kejadian kepada guru BK atau pihak sekolah yang berwenang. Jika ada bahaya langsung, segera cari bantuan.";
    }

    if (
        strpos($teks, "toilet") !== false ||
        strpos($teks, "kebersihan") !== false ||
        strpos($teks, "sampah") !== false
    ) {
        return "Terima kasih atas masukannya mengenai kebersihan sekolah. Masukan ini dapat menjadi bahan evaluasi agar lingkungan sekolah lebih bersih dan nyaman. Semoga pihak terkait dapat menindaklanjutinya.";
    }

    if (
        strpos($teks, "wifi") !== false ||
        strpos($teks, "internet") !== false ||
        strpos($teks, "komputer") !== false ||
        strpos($teks, "laboratorium") !== false ||
        strpos($teks, "fasilitas") !== false
    ) {
        return "Terima kasih atas saran mengenai fasilitas sekolah. Ketersediaan dan kondisi fasilitas sangat penting untuk mendukung kegiatan belajar. Masukan ini dapat menjadi pertimbangan dalam evaluasi sekolah.";
    }

    if (
        strpos($teks, "guru") !== false ||
        strpos($teks, "mengajar") !== false ||
        strpos($teks, "pelajaran") !== false ||
        strpos($teks, "pembelajaran") !== false
    ) {
        return "Terima kasih sudah menyampaikan pendapat tentang pembelajaran. Semoga masukan ini dapat membantu meningkatkan kenyamanan belajar, komunikasi, dan kualitas pembelajaran di sekolah.";
    }

    if (
        strpos($teks, "kantin") !== false ||
        strpos($teks, "makanan") !== false ||
        strpos($teks, "harga") !== false
    ) {
        return "Terima kasih atas masukan tentang kantin sekolah. Kenyamanan, kebersihan, variasi makanan, dan harga merupakan hal yang layak diperhatikan untuk mendukung kebutuhan siswa.";
    }

    if (
        strpos($teks, "terima kasih") !== false ||
        strpos($teks, "bagus") !== false ||
        strpos($teks, "senang") !== false ||
        strpos($teks, "puas") !== false
    ) {
        return "Terima kasih atas apresiasi yang diberikan! Senang mengetahui ada hal positif yang dirasakan. Semoga hal baik tersebut dapat dipertahankan dan terus ditingkatkan.";
    }

    if ($rating <= 2) {
        return "Terima kasih sudah berani menyampaikan kritik. Kami memahami bahwa ada hal yang perlu diperbaiki. Masukanmu akan tercatat sebagai bahan evaluasi, dan semoga pihak sekolah dapat meninjaunya.";
    }

    if ($rating >= 4) {
        return "Terima kasih atas saran dan penilaian positifmu! Masukan seperti ini dapat membantu sekolah mempertahankan hal-hal yang sudah baik dan terus berkembang.";
    }

    return "Terima kasih atas saran dan kritikmu mengenai " .
        strtolower($kategori) .
        ". Masukanmu sudah tercatat sebagai bahan evaluasi. Semoga dapat membantu menjadikan SMK Muhammadiyah 4 Yogyakarta semakin baik.";
}

$error = "";
$success = isset($_GET["success"]);
$namaForm = "";
$kategoriForm = "Fasilitas";
$pesanForm = "";
$ratingForm = 0;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $namaForm = trim($_POST["nama"] ?? "");
    $kategoriForm = $_POST["kategori"] ?? "";
    $pesanForm = trim($_POST["pesan"] ?? "");
    $ratingForm = filter_var(
        $_POST["rating"] ?? null,
        FILTER_VALIDATE_INT
    );

    $token = $_POST["csrf_token"] ?? "";

    $kategoriValid = [
        "Fasilitas",
        "Pembelajaran",
        "Guru",
        "Kebersihan",
        "Kantin",
        "Lingkungan",
        "Lainnya"
    ];

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Sesi formulir tidak valid. Muat ulang halaman.";
    } elseif ($namaForm === "") {
        $namaForm = "Siswa";
    }

    if ($error === "" && mb_strlen($namaForm) > 100) {
        $error = "Nama maksimal 100 karakter.";
    }

    if (
        $error === "" &&
        !in_array($kategoriForm, $kategoriValid, true)
    ) {
        $error = "Pilih kategori yang tersedia.";
    }

    if ($error === "" && ($ratingForm === false ||
        $ratingForm === null ||
        $ratingForm < 1 ||
        $ratingForm > 5)
    ) {
        $error = "Pilih rating bintang dari 1 sampai 5.";
    }

    if ($error === "" && $pesanForm === "") {
        $error = "Saran atau kritik tidak boleh kosong.";
    }

    if ($error === "" && mb_strlen($pesanForm) > 3000) {
        $error = "Pesan maksimal 3000 karakter.";
    }

    if ($error === "") {
        $balasan = balasanChatbot(
            $pesanForm,
            $kategoriForm,
            $ratingForm
        );

        try {
            $stmt = $conn->prepare(
                "INSERT INTO feedback
                (nama, kategori, rating, pesan, balasan_bot)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssiss",
                $namaForm,
                $kategoriForm,
                $ratingForm,
                $pesanForm,
                $balasan
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            header("Location: index.php?success=1#feedback-list");
            exit;
        } catch (mysqli_sql_exception $e) {
            error_log($e->getMessage());
            $error = "Pesan belum berhasil disimpan. Coba lagi.";
        }
    }
}

$feedbackList = [];

$result = $conn->query(
    "SELECT id, nama, kategori, rating, pesan, balasan_bot, created_at
     FROM feedback
     ORDER BY created_at DESC, id DESC
     LIMIT 50"
);

while ($row = $result->fetch_assoc()) {
    $feedbackList[] = $row;
}

$totalFeedback = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM feedback"
)->fetch_assoc()["total"];

$ratingSummary = $conn->query(
    "SELECT AVG(rating) AS rata FROM feedback"
)->fetch_assoc();

$rataRating = $ratingSummary["rata"] !== null
    ? number_format((float) $ratingSummary["rata"], 1)
    : "0.0";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#10152b">
    <title>Saran & Kritik | SMK Muhammadiyah 4 Yogyakarta</title>
    <link rel="stylesheet" href="style.css">
    <script>
        try {
            const theme = JSON.parse(
                localStorage.getItem("saran-sekolah-theme") || "{}"
            );
            document.documentElement.dataset.theme =
                theme.mode === "light" ? "light" : "dark";
        } catch (e) {
            document.documentElement.dataset.theme = "dark";
        }
    </script>
</head>
<body>

<header class="navbar">
    <a href="#" class="brand">
        <span class="brand-icon">S</span>
        <span>
            <strong>SuaraSekolah</strong>
            <small>SMK Muhammadiyah 4 Yogyakarta</small>
        </span>
    </a>

    <div class="nav-actions">
        <span class="live-label"><i></i> Ruang aspirasi siswa</span>
        <button class="theme-toggle" id="themeToggle"
                type="button" aria-label="Ganti tema">☀️</button>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow"><span class="eyebrow-dot"></span>
                YOUR VOICE MATTERS</span>

            <h1>Suara kamu,<br>perubahan <span>kita.</span></h1>

            <p>
                Punya saran atau kritik untuk sekolah?
                Sampaikan pendapatmu. Setiap masukan adalah langkah
                menuju lingkungan belajar yang lebih baik.
            </p>

            <a href="#form-saran" class="primary-link">
                Sampaikan aspirasi <span>↗</span>
            </a>

            <div class="hero-note">
                <span class="note-icon">✦</span>
                <span>Jujur, sopan, dan saling menghargai.</span>
            </div>
        </div>

        <div class="hero-visual">
            <div class="orb orb-one"></div>
            <div class="orb orb-two"></div>

            <div class="chat-preview">
                <div class="chat-preview-top">
                    <div class="bot-avatar">✦</div>
                    <div>
                        <strong>Asisten Sekolah</strong>
                        <small><i></i> Siap menanggapi</small>
                    </div>
                    <span class="more-dots">•••</span>
                </div>

                <div class="preview-message user-message">
                    Semoga fasilitas belajar bisa ditingkatkan.
                </div>

                <div class="preview-message bot-message">
                    <span class="mini-bot">✦</span>
                    Terima kasih atas masukannya! Fasilitas yang baik
                    sangat membantu proses belajar. ✨
                </div>

                <div class="preview-rating">
                    <span>Contoh rating</span>
                    <span class="gold-stars">★★★★★</span>
                </div>
            </div>

            <div class="floating-badge">
                <span>✦</span>
                <div>
                    <strong>Suara kamu berarti</strong>
                    <small>Bangun sekolah lebih baik</small>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-icon violet">✉</span>
            <div>
                <p>Total aspirasi</p>
                <strong><?= number_format($totalFeedback) ?></strong>
            </div>
        </article>

        <article class="stat-card">
            <span class="stat-icon gold">★</span>
            <div>
                <p>Rata-rata rating</p>
                <strong><?= e($rataRating) ?><small> / 5.0</small></strong>
            </div>
        </article>

        <article class="stat-card">
            <span class="stat-icon blue">✦</span>
            <div>
                <p>Balasan chatbot</p>
                <strong><?= number_format($totalFeedback) ?></strong>
            </div>
        </article>
    </section>

    <section class="content-grid" id="form-saran">
        <div class="form-card">
            <div class="section-heading">
                <span class="section-kicker">FORM ASPIRASI</span>
                <h2>Apa yang ingin kamu sampaikan?</h2>
                <p>Bagikan pengalamanmu agar sekolah bisa terus berkembang.</p>
            </div>

            <?php if ($success): ?>
                <div class="notice success-notice">
                    <span>✓</span>
                    <div>
                        <strong>Aspirasi berhasil dikirim!</strong>
                        <p>Chatbot sudah memberikan tanggapan untuk pesanmu.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="notice error-notice">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php#form-saran" id="feedbackForm">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION["csrf_token"]) ?>">

                <label for="nama">Nama <span class="optional">Opsional</span></label>
                <input type="text" id="nama" name="nama"
                       maxlength="100"
                       placeholder="Nama kamu (boleh dikosongkan)"
                       value="<?= e($namaForm) ?>">

                <label for="kategori">Kategori aspirasi</label>
                <select name="kategori" id="kategori" required>
                    <?php
                    $categories = [
                        "Fasilitas",
                        "Pembelajaran",
                        "Guru",
                        "Kebersihan",
                        "Kantin",
                        "Lingkungan",
                        "Lainnya"
                    ];

                    foreach ($categories as $category):
                    ?>
                        <option value="<?= e($category) ?>"
                            <?= $kategoriForm === $category ? "selected" : "" ?>>
                            <?= e($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <fieldset class="rating-field">
                    <legend>Bagaimana penilaianmu terhadap sekolah?</legend>
                    <p class="field-hint">Pilih bintang sebelum menulis komentar.</p>

                    <div class="star-picker" id="starPicker"
                         role="radiogroup" aria-label="Rating sekolah">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star-button"
                                data-rating="<?= $i ?>"
                                role="radio"
                                aria-checked="false"
                                aria-label="<?= $i ?> dari 5 bintang">★</button>
                        <?php endfor; ?>
                    </div>

                    <div class="rating-caption" id="ratingCaption">
                        Pilih rating kamu
                    </div>

                    <input type="hidden" name="rating" id="rating"
                           value="<?= e($ratingForm ?: "") ?>" required>
                </fieldset>

                <label for="pesan">Saran dan kritik kamu</label>
                <textarea id="pesan" name="pesan" rows="5"
                    maxlength="3000"
                    placeholder="Ceritakan saran, kritik, atau pengalamanmu di sekolah..."
                    required><?= e($pesanForm) ?></textarea>

                <div class="textarea-footer">
                    <span>Gunakan bahasa yang sopan dan jelas.</span>
                    <span id="charCount">0 / 3000</span>
                </div>

                <label class="consent">
                    <input type="checkbox" id="confirm" required>
                    <span>Saya mengisi dengan jujur dan tidak mengandung hinaan.</span>
                </label>

                <button type="submit" class="submit-button" id="submitButton">
                    <span>Kirim aspirasi</span>
                    <span class="submit-arrow">↗</span>
                </button>

                <p class="privacy-note">✦ Jangan cantumkan kata sandi atau data pribadi sensitif.</p>
            </form>
        </div>

        <aside class="side-info">
            <div class="assistant-card">
                <div class="assistant-heading">
                    <div class="assistant-avatar">✦</div>
                    <div>
                        <span class="section-kicker">AI ASSISTANT</span>
                        <h3>Asisten Sekolah</h3>
                    </div>
                    <span class="online-dot"></span>
                </div>

                <p>
                    Halo! Aku siap menanggapi aspirasi kamu secara otomatis.
                    Tulis pesan yang jelas agar balasanku lebih relevan.
                </p>

                <div class="assistant-divider"></div>

                <span class="suggestion-title">Contoh topik aspirasi</span>
                <div class="topic-chips">
                    <span>✦ Fasilitas</span>
                    <span>✦ Pembelajaran</span>
                    <span>✦ Kebersihan</span>
                    <span>✦ Kantin</span>
                </div>

                <div class="assistant-foot">
                    <span class="assistant-pulse"></span>
                    Balasan otomatis setelah pesan dikirim
                </div>
            </div>

            <div class="guideline-card">
                <span class="guideline-icon">◎</span>
                <h3>Sampaikan dengan bijak</h3>
                <p>Fokus pada masalah dan usulan perbaikan. Hindari menyebarkan data pribadi atau tuduhan yang belum terverifikasi.</p>
            </div>
        </aside>
    </section>

    <section class="feedback-section" id="feedback-list">
        <div class="feedback-heading">
            <div>
                <span class="section-kicker">SUARA KOMUNITAS</span>
                <h2>Aspirasi yang sudah disampaikan</h2>
                <p>Masukan dan tanggapan chatbot yang tersimpan di sistem.</p>
            </div>
            <span class="total-pill"><?= number_format($totalFeedback) ?> aspirasi</span>
        </div>

        <?php if (count($feedbackList) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">✦</div>
                <h3>Jadilah yang pertama bersuara</h3>
                <p>Belum ada aspirasi. Kirim saran atau kritik pertama kamu.</p>
            </div>
        <?php else: ?>
            <div class="feedback-list">
                <?php foreach ($feedbackList as $item): ?>
                    <article class="feedback-card">
                        <div class="feedback-top">
                            <div class="feedback-author">
                                <div class="author-avatar">
                                    <?= e(mb_strtoupper(mb_substr($item["nama"], 0, 1))) ?>
                                </div>
                                <div>
                                    <strong><?= e($item["nama"]) ?></strong>
                                    <small><?= e(date("d M Y, H:i", strtotime($item["created_at"]))) ?></small>
                                </div>
                            </div>

                            <span class="category-badge">
                                <?= e($item["kategori"]) ?>
                            </span>
                        </div>

                        <div class="feedback-rating"
                             aria-label="Rating <?= (int) $item["rating"] ?> dari 5">
                            <span class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="<?= $i <= (int) $item["rating"] ? "filled" : "empty" ?>">★</span>
                                <?php endfor; ?>
                            </span>
                            <strong><?= (int) $item["rating"] ?>.0</strong>
                        </div>

                        <p class="feedback-message"><?= nl2br(e($item["pesan"])) ?></p>

                        <div class="bot-reply">
                            <div class="reply-avatar">✦</div>
                            <div class="reply-content">
                                <div class="reply-title">
                                    <strong>Asisten Sekolah</strong>
                                    <span>Balasan otomatis</span>
                                </div>
                                <p><?= nl2br(e($item["balasan_bot"])) ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <a href="#" class="footer-brand">Suara<span>Sekolah.</span></a>
    <p>Tempat aspirasi untuk lingkungan sekolah yang lebih baik.</p>
    <span>SMK Muhammadiyah 4 Yogyakarta · <?= date("Y") ?></span>
</footer>

<script src="script.js"></script>
</body>
</html>
