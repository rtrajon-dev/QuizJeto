<?php
/**
 * Quiz game engine (server-authoritative).
 *
 * Game state lives in $_SESSION only; questions/answers come from SQLite.
 * The browser NEVER receives the correct option until after it has answered,
 * and the score is computed here — so it cannot be tampered with.
 *
 * Actions (?action=):
 *   start  (POST) → begins a new game, returns the first question
 *   answer (POST: choice=a|b|c|d|'') → scores current Q, returns result + next Q
 *   result (POST) → returns final score and clears the game
 */

header('Content-type: application/json');

// Ensure sessions persist on shared hosting (cPanel)
if (php_sapi_name() !== 'cli') {
    ini_set('session.save_path', __DIR__ . '/sessions');
    if (!is_dir(__DIR__ . '/sessions')) {
        @mkdir(__DIR__ . '/sessions', 0755, true);
    }
}
session_start();
require_once __DIR__ . '/db.php';

const QUIZ_TOTAL   = 15;  // questions per game
const QUIZ_SECONDS = 30;  // seconds per question

// --- gate: must be "registered" (session set after OTP/name step) ---
if (empty($_SESSION['phone'])) {
    http_response_code(403);
    echo json_encode(['error' => 'not_registered']);
    exit;
}

$pdo    = db();
$action = $_GET['action'] ?? '';

/** Pick QUIZ_TOTAL random question ids and reset session game state. */
function start_game(PDO $pdo): void
{
    $ids = $pdo->query('SELECT id FROM questions ORDER BY RANDOM() LIMIT ' . QUIZ_TOTAL)
               ->fetchAll(PDO::FETCH_COLUMN);
    $_SESSION['quiz'] = [
        'qids'    => $ids,
        'index'   => 0,
        'score'   => 0,
        'total'   => count($ids),
        'q_start' => time(),
        'done'    => false,
    ];
}

/** Return the current question for the client (WITHOUT the correct answer). */
function current_question(PDO $pdo): ?array
{
    $q = &$_SESSION['quiz'];
    if ($q['index'] >= count($q['qids'])) {
        return null;
    }
    $stmt = $pdo->prepare(
        'SELECT topic, question, option_a, option_b, option_c, option_d FROM questions WHERE id = ?'
    );
    $stmt->execute([$q['qids'][$q['index']]]);
    $r = $stmt->fetch();

    $q['q_start'] = time(); // (re)start the per-question timer on the server

    return [
        'no'       => $q['index'] + 1,
        'total'    => $q['total'],
        'topic'    => $r['topic'],
        'question' => $r['question'],
        'options'  => [
            'a' => $r['option_a'], 'b' => $r['option_b'],
            'c' => $r['option_c'], 'd' => $r['option_d'],
        ],
        'seconds'  => QUIZ_SECONDS,
    ];
}

if ($action === 'start') {
    start_game($pdo);
    echo json_encode(['ok' => true, 'question' => current_question($pdo)]);
    exit;
}

if ($action === 'answer') {
    if (empty($_SESSION['quiz']) || !empty($_SESSION['quiz']['done'])) {
        http_response_code(409);
        echo json_encode(['error' => 'no_active_game']);
        exit;
    }

    $choice = $_POST['choice'] ?? '';
    if (!in_array($choice, ['a', 'b', 'c', 'd', ''], true)) {
        http_response_code(422);
        echo json_encode(['error' => 'bad_choice']);
        exit;
    }

    $q = &$_SESSION['quiz'];

    $stmt = $pdo->prepare('SELECT correct_option FROM questions WHERE id = ?');
    $stmt->execute([$q['qids'][$q['index']]]);
    $correctOption = $stmt->fetchColumn();

    // server-side timer enforcement (+1s network grace)
    $timedOut  = (time() - ($q['q_start'] ?? time())) > (QUIZ_SECONDS + 1);
    $isCorrect = (!$timedOut && $choice === $correctOption);
    if ($isCorrect) {
        $q['score']++;
    }

    $q['index']++;
    $done = $q['index'] >= count($q['qids']);
    $next = null;
    if ($done) {
        $q['done'] = true;
    } else {
        $next = current_question($pdo);
    }

    echo json_encode([
        'correct'        => $isCorrect,
        'timedOut'       => $timedOut,
        'correct_option' => $correctOption,
        'score'          => $q['score'],
        'done'           => $done,
        'total'          => $q['total'],
        'next'           => $next,
    ]);
    exit;
}

if ($action === 'result') {
    $score = $_SESSION['quiz']['score'] ?? 0;
    $total = $_SESSION['quiz']['total'] ?? QUIZ_TOTAL;
    unset($_SESSION['quiz']);
    echo json_encode(['score' => $score, 'total' => $total]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
