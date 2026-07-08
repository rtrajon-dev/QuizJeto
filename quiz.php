<?php
// Ensure sessions persist on shared hosting (cPanel)
if (php_sapi_name() !== 'cli') {
    ini_set('session.save_path', __DIR__ . '/sessions');
    if (!is_dir(__DIR__ . '/sessions')) {
        @mkdir(__DIR__ . '/sessions', 0755, true);
    }
}
session_start();

// --- gate: only registered players (session set after OTP + name step) ---
if (empty($_SESSION['phone'])) {
    header('Location: /#register');
    exit;
}

$pageTitle = 'কুইজ চলছে — QuizJeeto';
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

      <!-- ===== Share your score ===== -->
      <div class="w-full mt-2">
        <div class="divider text-xs text-base-content/40">বন্ধুদের সাথে শেয়ার করো</div>
        <p class="text-sm text-base-content/70 mb-3">তোমার স্কোর বন্ধুদের সাথে শেয়ার করো — তাদেরও খেলতে চ্যালেঞ্জ জানাও! 🎯</p>
        <div class="flex flex-col sm:flex-row gap-2">
          <button onclick="shareScore()" class="btn btn-primary gap-2 flex-1">
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>
            শেয়ার করো
          </button>
          <button onclick="shareWhatsApp()" class="btn btn-success gap-2 flex-1">
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            হোয়াটসঅ্যাপ
          </button>
          <button id="btn-copy" onclick="copyLink()" class="btn btn-outline gap-2">🔗 লিংক কপি</button>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 mt-4 w-full sm:w-auto">
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
  const SITE_URL = 'https://quizjeto.patawise.com';   // link friends click to play

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

  // ===== Sharing: build the message from the (Bengali) score on screen =====
  function shareMessage() {
    const score = $('final-score').textContent;
    const total = $('final-total').textContent;
    return 'আমি QuizJeeto কুইজে ' + score + '/' + total + ' স্কোর করেছি! 🎉\n'
         + 'কুইজ খেলে জিতে নাও আকর্ষণীয় ডেটা প্যাক পুরস্কার! 🎁\n'
         + SITE_URL;
  }

  // Native share sheet (WhatsApp, Messenger, SMS…). Falls back to WhatsApp on desktop.
  async function shareScore() {
    if (navigator.share) {
      try {
        await navigator.share({ title: 'QuizJeeto', text: shareMessage() });
      } catch (e) { /* user dismissed the share sheet — ignore */ }
    } else {
      shareWhatsApp();
    }
  }

  // Open WhatsApp directly with the pre-filled message.
  function shareWhatsApp() {
    window.open('https://wa.me/?text=' + encodeURIComponent(shareMessage()), '_blank');
  }

  // Copy just the site link, with brief button feedback.
  async function copyLink() {
    const btn = $('btn-copy');
    try {
      await navigator.clipboard.writeText(SITE_URL);
      const label = btn.innerHTML;
      btn.innerHTML = '✅ কপি হয়েছে';
      setTimeout(() => { btn.innerHTML = label; }, 1500);
    } catch (e) {
      prompt('লিংক কপি করো:', SITE_URL);
    }
  }

  // kick off a fresh game
  (async () => {
    const data = await api('start');
    if (data && data.question) renderQuestion(data.question);
  })();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
