<?php
// ═══════════════════════════════════════════════════════
// FILE: data.php
// Endpoint tunggal untuk semua data slide.
// Akses: data.php?slide=1
//        data.php?slide=all   ← load semua sekaligus
// ═══════════════════════════════════════════════════════

// ── Konfigurasi Database ──────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'visualisasi_data'); // Sesuai dengan DB kamu
define('DB_USER', 'root');             // Username XAMPP default
define('DB_PASS', '');                 // Password XAMPP default (kosong)
define('DB_CHARSET', 'utf8mb4');

// ── CORS & Header (izinkan akses dari file HTML lokal) ──
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

// ── Koneksi PDO ───────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

// ── Helper: response JSON ─────────────────────────────
function respond(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function error(string $msg, int $code = 500): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ── Router ────────────────────────────────────────────
$slide = $_GET['slide'] ?? 'all';

try {
    $db = getDB();

    if ($slide === 'all') {
        // Load semua data sekaligus
        respond([
            'slide1' => getSlide1($db),
            'slide2' => getSlide2($db),
            'slide3' => getSlide3($db),
            'slide4' => getSlide4($db),
            'slide5' => getSlide5($db),
            'slide6' => getSlide6($db),
            'slide7' => getSlide7($db),
            'slide8' => getSlide8($db),
            'slide9' => getSlide9($db),
        ]);
    }

    match ((int)$slide) {
        1 => respond(getSlide1($db)),
        2 => respond(getSlide2($db)),
        3 => respond(getSlide3($db)),
        4 => respond(getSlide4($db)),
        5 => respond(getSlide5($db)),
        6 => respond(getSlide6($db)),
        7 => respond(getSlide7($db)),
        8 => respond(getSlide8($db)),
        9 => respond(getSlide9($db)),
        default => error('Slide tidak ditemukan', 404),
    };

} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage());
} catch (Throwable $e) {
    error('Server error: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════
// FUNGSI DATA PER SLIDE (Sudah Diisi Query)
// ═══════════════════════════════════════════════════════

/**
 * SLIDE 1 — Cover: distribusi risiko untuk donut chart
 * Output: { high, medium, low, total_jobs }
 */
function getSlide1(PDO $db): array {
    $stmt = $db->query("
        SELECT ai_risk_category, COUNT(*) as count 
        FROM jobs 
        WHERE ai_risk_category IS NOT NULL
        GROUP BY ai_risk_category
    ");
    $rows = $stmt->fetchAll();
    
    $result = ['high' => 0, 'medium' => 0, 'low' => 0, 'total_jobs' => 0];
    
    foreach ($rows as $row) {
        $cat = strtolower($row['ai_risk_category']);
        if (strpos($cat, 'high') !== false) {
            $result['high'] += $row['count'];
        } elseif (strpos($cat, 'medium') !== false) {
            $result['medium'] += $row['count'];
        } elseif (strpos($cat, 'low') !== false) {
            $result['low'] += $row['count'];
        }
        $result['total_jobs'] += $row['count'];
    }

    // Convert ke Persentase
    if ($result['total_jobs'] > 0) {
        $result['high'] = round(($result['high'] / $result['total_jobs']) * 100, 2);
        $result['medium'] = round(($result['medium'] / $result['total_jobs']) * 100, 2);
        $result['low'] = round(($result['low'] / $result['total_jobs']) * 100, 2);
    }
    
    return $result;
}

/**
 * SLIDE 2 — Bukti paradoks: Job Openings vs AI Risk (Bar Chart)
 * Output: array of { job_title, ai_risk_pct, job_openings }
 */
function getSlide2(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            job_title, 
            ROUND(AVG(ai_risk_score) * 100, 2) as ai_risk_pct, 
            ROUND(AVG(job_openings), 0) as job_openings 
        FROM jobs 
        GROUP BY job_title 
        ORDER BY ai_risk_pct DESC 
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

/**
 * SLIDE 3 — Salary vs Risk scatter chart
 * Output: array of { job_title, avg_salary_usd, ai_risk_score, category }
 */
function getSlide3(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            job_title, 
            ROUND(AVG(salary), 2) as avg_salary_usd, 
            ROUND(AVG(ai_risk_score) * 100, 2) as ai_risk_score, 
            MAX(ai_risk_category) as category 
        FROM jobs 
        GROUP BY job_title
    ");
    return $stmt->fetchAll();
}

/**
 * SLIDE 4 — Perbandingan gaji profesi
 * Output: array of { profession, avg_salary_usd, risk_level }
 */
function getSlide4(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            job_title as profession, 
            ROUND(AVG(salary), 2) as avg_salary_usd, 
            ROUND(AVG(ai_risk_score), 2) as risk_level 
        FROM jobs 
        WHERE job_title IN ('Data Analyst', 'Business Analyst', 'ML Engineer', 'AI Researcher', 'Data Scientist') 
        GROUP BY job_title 
        ORDER BY avg_salary_usd ASC
    ");
    return $stmt->fetchAll();
}

/**
 * SLIDE 5 — Pendidikan vs risiko
 * Output: array of { education_level, high_risk_pct, medium_risk_pct, low_risk_pct }
 */
function getSlide5(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            education_level, 
            ROUND(AVG(CASE WHEN ai_risk_category LIKE '%High%' THEN 1 ELSE 0 END), 4) as high_risk_pct,
            ROUND(AVG(CASE WHEN ai_risk_category LIKE '%Medium%' THEN 1 ELSE 0 END), 4) as medium_risk_pct,
            ROUND(AVG(CASE WHEN ai_risk_category LIKE '%Low%' THEN 1 ELSE 0 END), 4) as low_risk_pct
        FROM jobs 
        WHERE education_level IN ('Bachelor', 'Master', 'PhD', 'Bachelors', 'Masters')
        GROUP BY education_level
        ORDER BY education_level ASC
    ");
    return $stmt->fetchAll();
}

/**
 * SLIDE 6 — Top skills
 * Output: array of { skill_name, demand_score }
 */
function getSlide6(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            primary_skill as skill_name, 
            ROUND(AVG(skill_demand_score), 0) as demand_score 
        FROM jobs 
        GROUP BY primary_skill 
        ORDER BY demand_score DESC 
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

/**
 * SLIDE 7 — World heatmap
 * Output: array of { country_name, risk_index, avg_salary }
 */
function getSlide7(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            country as country_name, 
            ROUND(AVG(ai_risk_score), 2) as risk_index, 
            ROUND(AVG(salary), 2) as avg_salary 
        FROM jobs 
        GROUP BY country
    ");
    return $stmt->fetchAll();
}

/**
 * SLIDE 8 — Closing stats
 * Output: object key-value { stat_key: { value, description } }
 */
function getSlide8(PDO $db): array {
    $stmt = $db->query("SELECT COUNT(*) as total_records FROM jobs");
    $total = $stmt->fetchColumn();
    
    return [
        'total_data_analyzed' => $total,
        'message' => 'Di era AI, nilai strategis dan keahlian mendalam adalah pelindung karier terbaik.'
    ];
}

/**
 * SLIDE 9 — Salary per Experience Level (Box Plot)
 * Output: array of { experience_level, salary }
 * Semua baris dikembalikan agar JS bisa hitung distribusi (min, Q1, median, Q3, max, outlier)
 */
function getSlide9(PDO $db): array {
    $stmt = $db->query("
        SELECT 
            experience_level,
            ROUND(salary, 2) as salary
        FROM jobs
        WHERE experience_level IN ('Entry', 'Mid', 'Senior')
          AND salary IS NOT NULL
          AND salary > 0
        ORDER BY 
            CASE experience_level
                WHEN 'Entry'  THEN 1
                WHEN 'Mid'    THEN 2
                WHEN 'Senior' THEN 3
                ELSE 4
            END
    ");
    return $stmt->fetchAll();
}