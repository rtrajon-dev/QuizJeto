<?php
session_start();

// --- gate: only registered players (session set after OTP + name step) ---
if (empty($_SESSION['phone'])) {
    header('Location: /#register');
    exit;
}

$pageTitle = 'কুইজ চলছে — QuizJeto';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/navbar.php';
?>

<section class="max-w-2xl mx-auto px-4 py-6 sm:py-10">

  <!-- ===== GAME STAGE ===== -->
  <div id="stage">
    <div class="flex items-center justify-between mb-4">
      <span id="progress" class="badge badge-lg badge-primary">প্রশ্ন ১/১৫</span>
      <div class="flex items-center gap-2">
        <span class="text-sm text-base-content/60">সময়</span>
        <span id="timer" class="countdown font-mono text-2xl text-warning"><span style="--value:30;"></span></span>
        <span class="text-sm">সেকেন্ড</span>
      </div>
    </div>
    <progress id="bar" class="progress progress-primary w-full mb-8" value="30" max="30"></progress>

    <div class="card bg-base-200 border border-base-content/10 shadow-xl">
      <div class="card-body p-5 sm:p-8">
        <span id="topic" class="badge badge-ghost badge-sm mb-1"></span>
        <h2 id="qtext" class="text-xl sm:text-2xl font-semibold leading-relaxed">লোড হচ্ছে…</h2>
        <div id="options" class="grid gap-3 mt-4"></div>
      </div>
    </div>
    <div class="text-center mt-3 text-sm text-base-content/60">
      স্কোর: <span id="score" class="font-bold text-base-content">০</span>
    </div>
  </div>

  <!-- ===== RESULT (hidden until finished) ===== -->
  <div id="result" class="hidden card bg-base-200 border border-base-content/10 shadow-xl text-center">
    <div class="card-body items-center">
      <div id="result-emoji" class="text-6xl">🎉</div>
      <h2 class="text-2xl font-bold">কুইজ শেষ!</h2>
      <p class="text-base-content/70">তোমার স্কোর</p>
      <div class="text-5xl font-bold"><span id="final-score">০</span><span class="text-2xl text-base-content/50">/<span id="final-total">১৫</span></span></div>
      <p id="result-msg" class="text-base-content/70"></p>
      <div class="flex flex-col sm:flex-row gap-3 mt-2 w-full sm:w-auto">
        <a href="/quiz.php" class="btn btn-primary">আবার খেলুন</a>
        <a href="/" class="btn btn-ghost">হোমে যান</a>
      </div>
    </div>
  </div>

</section>

<script>
  const API = 'quiz_api.php';
  const LETTERS = { a: 'ক', b: 'খ', c: 'গ', d: 'ঘ' };
  const SECONDS = 30;

  let timerId = null, timeLeft = SECONDS, answered = false, lastChoice = '';

  const $ = (id) => document.getElementById(id);
  const bn = (n) => String(n).replace(/[0-9]/g, d => '০১২৩৪৫৬৭৮৯'[d]);

  async function api(action, body) {
    const res = await fetch(API + '?action=' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body ? new URLSearchParams(body) : '',
    });
    if (res.status === 403) { window.location.href = '/#register'; return null; }
    return res.json().catch(() => ({}));
  }

  function startTimer() {
    clearInterval(timerId);
    timeLeft = SECONDS;
    updateTimer();
    timerId = setInterval(() => {
      timeLeft--;
      updateTimer();
      if (timeLeft <= 0) { clearInterval(timerId); submit(''); } // auto-submit timeout
    }, 1000);
  }
  function updateTimer() {
    $('timer').querySelector('span').style.setProperty('--value', Math.max(0, timeLeft));
    $('bar').value = Math.max(0, timeLeft);
    $('timer').classList.toggle('text-error', timeLeft <= 5);
  }

  function renderQuestion(q) {
    answered = false;
    lastChoice = '';
    $('progress').textContent = 'প্রশ্ন ' + bn(q.no) + '/' + bn(q.total);
    $('topic').textContent = q.topic;
    $('qtext').textContent = q.question;

    const box = $('options');
    box.innerHTML = '';
    ['a', 'b', 'c', 'd'].forEach(opt => {
      const btn = document.createElement('button');
      btn.className = 'btn btn-outline btn-lg h-auto min-h-12 py-3 justify-start text-left whitespace-normal text-base sm:text-lg normal-case';
      btn.dataset.opt = opt;
      btn.innerHTML = '<span class="badge badge-neutral mr-2 shrink-0">' + LETTERS[opt] + '</span>' + escapeHtml(q.options[opt]);
      btn.onclick = () => submit(opt);
      box.appendChild(btn);
    });
    startTimer();
  }

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  async function submit(choice) {
    if (answered) return;
    answered = true;
    lastChoice = choice;
    clearInterval(timerId);

    // lock buttons
    document.querySelectorAll('#options button').forEach(b => (b.disabled = true));

    const res = await api('answer', { choice });
    if (!res) return;

    // reveal correct / wrong
    document.querySelectorAll('#options button').forEach(b => {
      const opt = b.dataset.opt;
      b.classList.remove('btn-outline');
      if (opt === res.correct_option) { b.classList.add('btn-success'); }
      else if (opt === lastChoice)     { b.classList.add('btn-error'); }
      else { b.classList.add('btn-ghost'); }
    });
    $('score').textContent = bn(res.score);

    setTimeout(() => {
      if (res.done) { showResult(); }
      else { renderQuestion(res.next); }
    }, 1200);
  }

  async function showResult() {
    const r = await api('result');
    if (!r) return;
    $('stage').classList.add('hidden');
    $('final-score').textContent = bn(r.score);
    $('final-total').textContent = bn(r.total);
    const pct = r.score / r.total;
    $('result-emoji').textContent = pct >= 0.8 ? '🏆' : pct >= 0.5 ? '🎉' : '💪';
    $('result-msg').textContent = pct >= 0.8
      ? 'দারুণ! তুমি আজকের সেরাদের একজন।'
      : pct >= 0.5 ? 'ভালো খেলেছ! আরও চেষ্টা করো।'
      : 'অনুশীলন চালিয়ে যাও — পরেরবার আরও ভালো হবে!';
    $('result').classList.remove('hidden');
  }

  // kick off a fresh game
  (async () => {
    const data = await api('start');
    if (data && data.question) renderQuestion(data.question);
  })();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
