<?php
$pageTitle = 'QuizJeto — কুইজ খেলুন, জিতুন';

require_once __DIR__ . '/db.php';

// --- Topics: count real questions per topic from the DB ---
$topicMeta = [
  '⚽' => 'ফিফা বিশ্বকাপ ২০২৬', '🏏' => 'বাংলাদেশ ক্রিকেট', '🇧🇩' => 'বাংলাদেশের ইতিহাস',
  '🔬' => 'সাধারণ বিজ্ঞান', '🌍' => 'বিশ্ব ভূগোল', '💡' => 'সাধারণ জ্ঞান',
  '🎬' => 'বিনোদন', '🏆' => 'খেলাধুলা',
];
$counts = db()->query('SELECT topic, COUNT(*) c FROM questions GROUP BY topic')->fetchAll();
$countByTopic = array_column($counts, 'c', 'topic');

$topics = [];
foreach ($topicMeta as $icon => $name) {
  $n = $countByTopic[$name] ?? 0;
  $topics[] = ['icon' => $icon, 'name' => $name, 'q' => bn($n) . 'টি প্রশ্ন'];
}

// --- Leaderboard: DUMMY players (social proof), shuffled once per day ---
// Not real users. A daily-stable random selection so it changes each day but
// stays consistent if a visitor refreshes.
$prizes = ['২০০০ MB ডেটা', '১৫০০ MB ডেটা', '১০০০ MB ডেটা', '৫০০ MB ডেটা', '৫০০ MB ডেটা'];

$pool = db()->query('SELECT name FROM dummy_players')->fetchAll(PDO::FETCH_COLUMN);

mt_srand((int) date('Ymd'));   // same seed all day → stable; changes tomorrow
shuffle($pool);
$picked = array_slice($pool, 0, 5);

// believable, non-increasing scores from 15 downward
$leaderboard = [];
$score = 15;
foreach ($picked as $i => $name) {
  if ($i > 0) {
    $score -= mt_rand(0, 1);     // sometimes equal, sometimes one lower
    $score = max(10, $score);
  }
  $leaderboard[] = [
    'rank'  => $i + 1,
    'name'  => $name,
    'score' => bn($score) . '/১৫',
    'prize' => $prizes[$i] ?? '৫০০ MB ডেটা',
  ];
}
mt_srand();   // restore normal randomness for the rest of the page

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/navbar.php';
?>

<!-- ============ HERO ============ -->
<section class="relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-br from-primary/20 via-base-100 to-secondary/10"></div>
  <div class="relative max-w-6xl mx-auto px-4 lg:px-8 py-10 sm:py-16 lg:py-24 grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
    <!-- Left: copy -->
    <div class="text-center lg:text-left">
      <div class="badge badge-accent badge-outline mb-4 gap-1">🔥 প্রতিদিন নতুন কুইজ</div>
      <h1 class="text-3xl sm:text-4xl lg:text-6xl font-bold leading-tight">
        জ্ঞান দিয়ে <span style="background:linear-gradient(90deg,hsl(var(--p)),hsl(var(--s)));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;">জিতুন</span> পুরস্কার
      </h1>
      <p class="py-5 sm:py-6 text-base sm:text-lg text-base-content/70 max-w-lg mx-auto lg:mx-0">
        ১৫টি প্রশ্ন, প্রতি প্রশ্নে ৩০ সেকেন্ড। সঠিক উত্তর দিন, লিডারবোর্ডে উঠুন এবং
        জিতে নিন ডেটা ও আকর্ষণীয় পুরস্কার। মাত্র <span class="font-bold text-accent">২.৭৮ টাকা</span>/সেশন।
      </p>
      <div class="flex flex-col sm:flex-row gap-3 sm:justify-center lg:justify-start">
        <a href="#register" class="btn btn-primary btn-lg w-full sm:w-auto">এখনই খেলুন</a>
        <a href="#how" class="btn btn-outline btn-lg w-full sm:w-auto">কীভাবে কাজ করে?</a>
      </div>
      <div class="grid grid-cols-3 gap-2 mt-8 text-center text-xs sm:text-sm text-base-content/60">
        <div><span class="text-xl sm:text-2xl font-bold text-base-content">৫০K+</span><br>খেলোয়াড়</div>
        <div><span class="text-xl sm:text-2xl font-bold text-base-content">৩০০০+</span><br>প্রশ্ন</div>
        <div><span class="text-xl sm:text-2xl font-bold text-base-content">দৈনিক</span><br>পুরস্কার</div>
      </div>
    </div>

    <!-- Right: registration / OTP card -->
    <div id="register" class="card bg-base-200 shadow-xl border border-base-content/10 scroll-mt-20">
      <div class="card-body p-5 sm:p-8">
        <h2 class="card-title text-2xl">শুরু করুন ৩ ধাপে</h2>
        <p class="text-base-content/60 text-sm">রবি / এয়ারটেল নম্বর দিয়ে রেজিস্টার করুন</p>

        <!-- Step 1: phone -->
        <div id="step-phone" class="mt-4 space-y-3">
          <label class="form-control w-full">
            <div class="label"><span class="label-text">মোবাইল নম্বর</span></div>
            <label class="input input-bordered flex items-center gap-2">
              <span class="text-base-content/50">+৮৮</span>
              <input type="tel" id="phone" inputmode="numeric" maxlength="11" placeholder="01XXXXXXXXX" class="grow" />
            </label>
            <div class="label"><span class="label-text-alt text-base-content/50">শুধু রবি ও এয়ারটেল নম্বর সমর্থিত</span></div>
          </label>
          <p id="err-phone" class="text-error text-sm hidden"></p>
          <button id="btn-send" onclick="goToOtp()" class="btn btn-primary w-full">OTP পাঠান</button>
        </div>

        <!-- Step 2: OTP (hidden until step 1) -->
        <div id="step-otp" class="mt-4 space-y-3 hidden">
          <p class="text-sm text-base-content/70">আপনার নম্বরে পাঠানো ৪-সংখ্যার কোডটি লিখুন</p>
          <div class="flex gap-2 sm:gap-3 justify-center" dir="ltr">
            <input type="text" maxlength="1" inputmode="numeric" class="input input-bordered w-12 h-14 sm:w-14 text-center text-xl otp-box" />
            <input type="text" maxlength="1" inputmode="numeric" class="input input-bordered w-12 h-14 sm:w-14 text-center text-xl otp-box" />
            <input type="text" maxlength="1" inputmode="numeric" class="input input-bordered w-12 h-14 sm:w-14 text-center text-xl otp-box" />
            <input type="text" maxlength="1" inputmode="numeric" class="input input-bordered w-12 h-14 sm:w-14 text-center text-xl otp-box" />
          </div>
          <p id="otp-sent-to" class="text-xs text-base-content/50 text-center"></p>
          <p id="err-otp" class="text-error text-sm text-center hidden"></p>
          <button id="btn-verify" onclick="verifyOtp()" class="btn btn-primary w-full">যাচাই করুন</button>
          <button onclick="resetReg()" class="btn btn-ghost btn-sm w-full">← নম্বর পরিবর্তন করুন</button>
        </div>

        <!-- Step 3: name (optional) -->
        <div id="step-name" class="mt-4 space-y-3 hidden">
          <div class="text-center text-4xl">✅</div>
          <p class="text-center text-sm text-base-content/70">যাচাই সফল! লিডারবোর্ডে দেখানোর জন্য একটি নাম দাও</p>
          <label class="form-control w-full">
            <div class="label"><span class="label-text">তোমার নাম <span class="text-base-content/40">(ঐচ্ছিক)</span></span></div>
            <input type="text" id="display-name" maxlength="30" placeholder="যেমন: রাকিব হাসান" class="input input-bordered w-full" />
          </label>
          <p id="err-name" class="text-error text-sm hidden"></p>
          <button id="btn-name" onclick="saveName()" class="btn btn-primary w-full">সংরক্ষণ করুন</button>
          <button onclick="saveName(true)" class="btn btn-ghost btn-sm w-full">এড়িয়ে যান</button>
        </div>

        <!-- Step 4: success -->
        <div id="step-done" class="mt-4 hidden text-center space-y-3">
          <div class="text-5xl">🎉</div>
          <h3 class="font-bold text-lg">স্বাগতম<span id="welcome-name"></span>!</h3>
          <p class="text-sm text-base-content/70">আপনি এখন কুইজ খেলতে প্রস্তুত।</p>
          <a href="/quiz.php" class="btn btn-accent w-full">কুইজ শুরু করুন →</a>
        </div>

        <div class="divider text-xs text-base-content/40">নিরাপদ ও বিশ্বস্ত</div>
        <p class="text-xs text-base-content/40 text-center">
          সেশন প্রতি ২.৭৮ টাকা (ভ্যাট, সার্ভিস চার্জ ও সম্পূরক শুল্কসহ) আপনার ব্যালেন্স থেকে কাটা হবে।
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section id="how" class="max-w-6xl mx-auto px-4 lg:px-8 py-12 sm:py-16 scroll-mt-20">
  <h2 class="text-2xl sm:text-3xl font-bold text-center mb-2">যেভাবে খেলবেন</h2>
  <p class="text-center text-base-content/60 mb-10">মাত্র তিনটি সহজ ধাপ</p>
  <ul class="steps steps-horizontal w-full">
    <li class="step step-primary" data-content="১">
      <div class="p-4"><div class="text-3xl mb-2">📱</div><h3 class="font-semibold">নম্বর দিন</h3><p class="text-sm text-base-content/60">রবি/এয়ারটেল নম্বর দিয়ে রেজিস্টার করুন</p></div>
    </li>
    <li class="step step-primary" data-content="২">
      <div class="p-4"><div class="text-3xl mb-2">🔐</div><h3 class="font-semibold">OTP যাচাই</h3><p class="text-sm text-base-content/60">SMS-এ পাঠানো কোড দিয়ে নিশ্চিত করুন</p></div>
    </li>
    <li class="step step-primary" data-content="৩">
      <div class="p-4"><div class="text-3xl mb-2">🏆</div><h3 class="font-semibold">খেলুন ও জিতুন</h3><p class="text-sm text-base-content/60">১৫টি প্রশ্নের উত্তর দিন, পুরস্কার জিতুন</p></div>
    </li>
  </ul>
</section>

<!-- ============ TOPICS ============ -->
<section id="topics" class="bg-base-200/50 py-12 sm:py-16 scroll-mt-16">
  <div class="max-w-6xl mx-auto px-4 lg:px-8">
    <h2 class="text-2xl sm:text-3xl font-bold text-center mb-2">বিষয়সমূহ</h2>
    <p class="text-center text-base-content/60 mb-10">আপনার পছন্দের বিষয়ে কুইজ খেলুন</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach ($topics as $t): ?>
      <div class="card bg-base-100 hover:bg-base-300 transition-colors cursor-pointer border border-base-content/10 hover:border-primary/50">
        <div class="card-body items-center text-center p-5">
          <div class="text-4xl"><?= $t['icon'] ?></div>
          <h3 class="font-semibold mt-2"><?= $t['name'] ?></h3>
          <span class="badge badge-ghost badge-sm"><?= $t['q'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ REWARDS ============ -->
<section id="rewards" class="max-w-6xl mx-auto px-4 lg:px-8 py-12 sm:py-16 scroll-mt-16">
  <h2 class="text-2xl sm:text-3xl font-bold text-center mb-2">পুরস্কার</h2>
  <p class="text-center text-base-content/60 mb-10">প্রতিদিন সেরাদের জন্য থাকছে বিশেষ পুরস্কার</p>
  <div class="grid md:grid-cols-3 gap-6">
    <div class="card bg-gradient-to-br from-warning/20 to-base-200 border border-warning/30">
      <div class="card-body items-center text-center">
        <div class="text-5xl">🥇</div><h3 class="card-title">দৈনিক চ্যাম্পিয়ন</h3>
        <p class="text-base-content/70">সর্বোচ্চ স্কোরকারী পাবেন ২০০০ MB ডেটা + বোনাস পয়েন্ট</p>
      </div>
    </div>
    <div class="card bg-gradient-to-br from-secondary/20 to-base-200 border border-secondary/30">
      <div class="card-body items-center text-center">
        <div class="text-5xl">📶</div><h3 class="card-title">ডেটা বোনাস</h3>
        <p class="text-base-content/70">টপ ১০ খেলোয়াড় প্রতিদিন ইন্টারনেট ডেটা বোনাস পাবেন</p>
      </div>
    </div>
    <div class="card bg-gradient-to-br from-accent/20 to-base-200 border border-accent/30">
      <div class="card-body items-center text-center">
        <div class="text-5xl">🎁</div><h3 class="card-title">সাপ্তাহিক প্রাইজ</h3>
        <p class="text-base-content/70">সপ্তাহের সেরা খেলোয়াড়দের জন্য আকর্ষণীয় উপহার</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ LEADERBOARD ============ -->
<section id="leaderboard" class="bg-base-200/50 py-12 sm:py-16 scroll-mt-16">
  <div class="max-w-3xl mx-auto px-4 lg:px-8">
    <h2 class="text-2xl sm:text-3xl font-bold text-center mb-2">আজকের লিডারবোর্ড</h2>
    <p class="text-center text-base-content/60 mb-10">সেরা খেলোয়াড়রা এই মুহূর্তে</p>

    <div class="overflow-x-auto rounded-box border border-base-content/10 bg-base-100">
      <table class="table">
        <thead>
          <tr><th>র‍্যাঙ্ক</th><th>খেলোয়াড়</th><th>স্কোর</th><th class="text-right">পুরস্কার</th></tr>
        </thead>
        <tbody>
          <?php foreach ($leaderboard as $row): ?>
          <tr class="<?= $row['rank'] <= 3 ? 'font-semibold' : '' ?>">
            <td>
              <?php if ($row['rank'] == 1): ?>🥇<?php elseif ($row['rank'] == 2): ?>🥈<?php elseif ($row['rank'] == 3): ?>🥉<?php else: ?><span class="text-base-content/50"><?= bn($row['rank']) ?></span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge badge-primary badge-sm"><?= htmlspecialchars($row['score'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="text-right text-base-content/70"><?= htmlspecialchars($row['prize'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ============ FINAL CTA ============ -->
<section class="max-w-4xl mx-auto px-4 py-12 sm:py-16 text-center">
  <div class="card bg-gradient-to-r from-primary to-secondary text-primary-content">
    <div class="card-body items-center">
      <h2 class="text-2xl sm:text-3xl font-bold">আজই আপনার জ্ঞান পরীক্ষা করুন!</h2>
      <p class="opacity-90">হাজারো খেলোয়াড়ের সাথে প্রতিযোগিতা করুন এবং পুরস্কার জিতুন।</p>
      <a href="#register" class="btn btn-neutral btn-lg mt-2">এখনই শুরু করুন</a>
    </div>
  </div>
</section>

<script>
  // --- UI-only demo flow (no real backend yet) ---
  // --- Endpoints (relative to docroot) ---
  const SEND_OTP_URL   = 'bdapps/send_otp.php';
  const VERIFY_OTP_URL = 'bdapps/verify_otp.php';

  // State carried between the two steps
  let referenceNo = null;
  let currentPhone = '';

  const $ = (id) => document.getElementById(id);

  function showError(el, msg) {
    el.textContent = msg;
    el.classList.remove('hidden');
  }
  function hideError(el) { el.classList.add('hidden'); }

  function setLoading(btn, loading, label) {
    if (loading) {
      btn.dataset.label = btn.innerHTML;
      btn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> ' + (label || '');
      btn.disabled = true;
    } else {
      btn.innerHTML = btn.dataset.label || btn.innerHTML;
      btn.disabled = false;
    }
  }

  // STEP 1 — request OTP from bdapps via send_otp.php
  async function goToOtp() {
    const btn = $('btn-send');
    const errEl = $('err-phone');
    hideError(errEl);

    const phone = $('phone').value.trim();
    if (!/^01[3-9]\d{8}$/.test(phone)) {
      showError(errEl, 'সঠিক ১১-সংখ্যার মোবাইল নম্বর দিন (যেমন: 01812345678)');
      return;
    }

    setLoading(btn, true, 'পাঠানো হচ্ছে...');
    try {
      const res = await fetch(SEND_OTP_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_mobile: phone }),
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.referenceNo) {
        showError(errEl, data.message || data.statusDetail || data.error || 'OTP পাঠানো যায়নি। আবার চেষ্টা করুন।');
        return;
      }

      referenceNo = data.referenceNo;
      currentPhone = phone;
      $('otp-sent-to').textContent = '+৮৮' + phone + ' নম্বরে কোড পাঠানো হয়েছে';
      $('step-phone').classList.add('hidden');
      $('step-otp').classList.remove('hidden');
      document.querySelector('.otp-box')?.focus();
    } catch (e) {
      showError(errEl, 'নেটওয়ার্ক সমস্যা। ইন্টারনেট সংযোগ দেখুন।');
    } finally {
      setLoading(btn, false);
    }
  }

  // STEP 2 — verify OTP via verify_otp.php
  async function verifyOtp() {
    const btn = $('btn-verify');
    const errEl = $('err-otp');
    hideError(errEl);

    const otp = Array.from(document.querySelectorAll('.otp-box')).map(b => b.value).join('');
    if (!/^\d{4}$/.test(otp)) {
      showError(errEl, '৪-সংখ্যার কোডটি সম্পূর্ণ লিখুন');
      return;
    }
    if (!referenceNo) {
      showError(errEl, 'সেশন মেয়াদোত্তীর্ণ। আবার OTP নিন।');
      return;
    }

    setLoading(btn, true, 'যাচাই হচ্ছে...');
    try {
      const res = await fetch(VERIFY_OTP_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ Otp: otp, referenceNo }),
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.subscriptionStatus) {
        showError(errEl, data.statusDetail || data.message || data.error || 'ভুল বা মেয়াদোত্তীর্ণ OTP।');
        return;
      }

      // Verified — move to the (optional) name step
      $('step-otp').classList.add('hidden');
      $('step-name').classList.remove('hidden');
      $('display-name').focus();
    } catch (e) {
      showError(errEl, 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।');
    } finally {
      setLoading(btn, false);
    }
  }

  // STEP 3 — save name (or skip) → create user + session, then show success
  async function saveName(skip = false) {
    const btn = $('btn-name');
    const errEl = $('err-name');
    hideError(errEl);

    const name = skip ? '' : $('display-name').value.trim();

    setLoading(btn, true, 'অপেক্ষা করুন...');
    try {
      const res = await fetch('register_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_mobile: currentPhone, display_name: name }),
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.ok) {
        showError(errEl, data.error || 'সংরক্ষণ ব্যর্থ হয়েছে। আবার চেষ্টা করুন।');
        return;
      }

      $('welcome-name').textContent = data.display_name ? ' ' + data.display_name : '';
      $('step-name').classList.add('hidden');
      $('step-done').classList.remove('hidden');
    } catch (e) {
      showError(errEl, 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।');
    } finally {
      setLoading(btn, false);
    }
  }

  function resetReg() {
    hideError($('err-otp'));
    referenceNo = null;
    document.querySelectorAll('.otp-box').forEach(b => (b.value = ''));
    $('step-otp').classList.add('hidden');
    $('step-phone').classList.remove('hidden');
    $('phone').focus();
  }

  // OTP boxes: auto-advance, backspace-to-previous, submit on Enter
  document.querySelectorAll('.otp-box').forEach((box, i, arr) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '');       // digits only
      if (box.value && arr[i + 1]) arr[i + 1].focus();
    });
    box.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !box.value && arr[i - 1]) arr[i - 1].focus();
      if (e.key === 'Enter') verifyOtp();
    });
  });

  // Submit phone step on Enter
  $('phone').addEventListener('keydown', (e) => { if (e.key === 'Enter') goToOtp(); });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
