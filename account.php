<?php
// Ensure sessions persist on shared hosting (cPanel)
if (php_sapi_name() !== 'cli') {
    ini_set('session.save_path', __DIR__ . '/sessions');
    if (!is_dir(__DIR__ . '/sessions')) {
        @mkdir(__DIR__ . '/sessions', 0755, true);
    }
}
session_start();

// --- gate: only logged-in players ---
if (empty($_SESSION['phone'])) {
    header('Location: /#register');
    exit;
}

$pageTitle = 'আমার অ্যাকাউন্ট — QuizJeeto';
$phone   = $_SESSION['phone'];
$display = $_SESSION['display'] ?? (substr($phone, 0, 3) . '•••' . substr($phone, -3));

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/navbar.php';
?>

<section class="max-w-xl mx-auto px-4 py-8 sm:py-12">
  <h1 class="text-2xl sm:text-3xl font-bold mb-6">আমার অ্যাকাউন্ট</h1>

  <!-- Profile -->
  <div class="card bg-base-200 border border-base-content/10 shadow-sm mb-6">
    <div class="card-body">
      <div class="flex items-center gap-4">
        <div class="text-4xl">👤</div>
        <div>
          <div class="font-semibold text-lg"><?= htmlspecialchars($display, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="text-sm text-base-content/60"><?= htmlspecialchars(substr($phone, 0, 3) . '•••' . substr($phone, -3), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Subscription status -->
  <div class="card bg-base-200 border border-base-content/10 shadow-sm mb-6">
    <div class="card-body">
      <h2 class="card-title text-lg">সাবস্ক্রিপশন স্ট্যাটাস</h2>
      <p class="text-sm text-base-content/60">প্রতিদিন ২.৭৮ টাকা + (ভ্যাট + সম্পূরক শুল্ক + সার্ভিস চার্জ), অটো-রিনিউয়াল। শুধুমাত্র রবি ও এয়ারটেল গ্রাহকদের জন্য।</p>
      <div id="status-box" class="mt-2 text-sm">
        <span class="badge badge-ghost">যাচাই করা হয়নি</span>
      </div>
      <div class="card-actions mt-3">
        <button id="btn-status" onclick="checkStatus()" class="btn btn-outline btn-sm">স্ট্যাটাস চেক করুন</button>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="card bg-base-200 border border-base-content/10 shadow-sm">
    <div class="card-body gap-3">
      <a href="/quiz.php" class="btn btn-primary">কুইজ খেলুন →</a>
      <button id="btn-unsub" onclick="unsubscribe()" class="btn btn-outline btn-error">আনসাবস্ক্রাইব করুন</button>
      <a href="/logout.php" class="btn btn-ghost">লগআউট</a>
      <p id="err" class="text-error text-sm hidden"></p>
    </div>
  </div>
</section>

<script>
  const $ = (id) => document.getElementById(id);
  const bn = (n) => String(n).replace(/[0-9]/g, d => '০১২৩৪৫৬৭৮৯'[d]);
  const PHONE = '<?= htmlspecialchars($phone, ENT_QUOTES, "UTF-8") ?>';   // logged-in user's own number

  function setLoading(btn, on, label) {
    if (on) { btn.dataset.label = btn.innerHTML; btn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> ' + (label || ''); btn.disabled = true; }
    else { btn.innerHTML = btn.dataset.label || btn.innerHTML; btn.disabled = false; }
  }

  async function checkStatus() {
    const btn = $('btn-status');
    setLoading(btn, true, 'যাচাই হচ্ছে...');
    try {
      const res = await fetch('bdapps/check_subscription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_mobile: PHONE }),
      });
      const data = await res.json().catch(() => ({}));
      const box = $('status-box');
      if (data.isSubscribed) {
        box.innerHTML = '<span class="badge badge-success">সক্রিয় (Subscribed)</span>';
      } else if (data.subscriptionStatus) {
        box.innerHTML = '<span class="badge badge-warning">নিষ্ক্রিয় (Unsubscribed)</span>';
      } else {
        box.innerHTML = '<span class="badge badge-ghost">স্ট্যাটাস জানা যায়নি</span>';
      }
    } catch (e) {
      $('err').textContent = 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।';
      $('err').classList.remove('hidden');
    } finally {
      setLoading(btn, false);
    }
  }

  async function unsubscribe() {
    if (!confirm('আপনি কি নিশ্চিতভাবে আনসাবস্ক্রাইব করতে চান? এটি করলে আপনি লগআউট হয়ে যাবেন।')) return;
    const btn = $('btn-unsub');
    $('err').classList.add('hidden');
    setLoading(btn, true, 'অপেক্ষা করুন...');
    try {
      const res = await fetch('bdapps/unsubscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_mobile: PHONE }),
      });
      const data = await res.json().catch(() => ({}));
      if (data.success) {
        // Guideline #6: on unsubscribe, auto log out + redirect to login.
        window.location.href = '/logout.php';
      } else {
        $('err').textContent = data.error || 'আনসাবস্ক্রাইব ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
        $('err').classList.remove('hidden');
      }
    } catch (e) {
      $('err').textContent = 'নেটওয়ার্ক সমস্যা। আবার চেষ্টা করুন।';
      $('err').classList.remove('hidden');
    } finally {
      setLoading(btn, false);
    }
  }
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
