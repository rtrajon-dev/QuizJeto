<!-- Sticky top navbar -->
<div class="navbar bg-base-100/80 backdrop-blur sticky top-0 z-50 border-b border-base-content/10 px-4 lg:px-12">
  <div class="navbar-start">
    <div class="dropdown">
      <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
      </div>
      <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-200 rounded-box z-[1] mt-3 w-52 p-2 shadow">
        <li><a href="#how">যেভাবে খেলবেন</a></li>
        <li><a href="#topics">বিষয়সমূহ</a></li>
        <li><a href="#rewards">পুরস্কার</a></li>
        <li><a href="#leaderboard">লিডারবোর্ড</a></li>
      </ul>
    </div>
    <a href="/" class="btn btn-ghost px-2 text-lg sm:text-xl font-bold gap-1.5">
      <span class="text-xl sm:text-2xl">🧠</span>
      <span style="background:linear-gradient(90deg,hsl(var(--p)),hsl(var(--s)));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;">QuizJeto</span>
    </a>
  </div>
  <div class="navbar-center hidden lg:flex">
    <ul class="menu menu-horizontal px-1 gap-1 font-medium">
      <li><a href="#how">যেভাবে খেলবেন</a></li>
      <li><a href="#topics">বিষয়সমূহ</a></li>
      <li><a href="#rewards">পুরস্কার</a></li>
      <li><a href="#leaderboard">লিডারবোর্ড</a></li>
    </ul>
  </div>
  <div class="navbar-end gap-2">
    <!-- Light/Dark theme toggle -->
    <label class="swap swap-rotate btn btn-ghost btn-circle" title="থিম পরিবর্তন করুন">
      <input type="checkbox" id="theme-toggle" />
      <!-- sun icon (shows in dark mode → tap for light) -->
      <svg class="swap-on h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64 17l-.71.71a1 1 0 0 0 0 1.41 1 1 0 0 0 1.41 0l.71-.71A1 1 0 0 0 5.64 17zM5 12a1 1 0 0 0-1-1H3a1 1 0 0 0 0 2h1a1 1 0 0 0 1-1zm7-7a1 1 0 0 0 1-1V3a1 1 0 0 0-2 0v1a1 1 0 0 0 1 1zM5.64 7.05a1 1 0 0 0 .7.29 1 1 0 0 0 .71-.29 1 1 0 0 0 0-1.41l-.71-.71a1 1 0 0 0-1.41 1.41zM17 5.64a1 1 0 0 0 .7-.29l.71-.71a1 1 0 1 0-1.41-1.41l-.71.71A1 1 0 0 0 17 5.64zM21 11h-1a1 1 0 0 0 0 2h1a1 1 0 0 0 0-2zm-9 8a1 1 0 0 0-1 1v1a1 1 0 0 0 2 0v-1a1 1 0 0 0-1-1zm6.36-2a1 1 0 0 0-1.41 1.41l.71.71a1 1 0 0 0 1.41 0 1 1 0 0 0 0-1.41zM12 6.5A5.5 5.5 0 1 0 17.5 12 5.51 5.51 0 0 0 12 6.5z"/></svg>
      <!-- moon icon (shows in light mode → tap for dark) -->
      <svg class="swap-off h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64 13a1 1 0 0 0-1.05-.14 8.05 8.05 0 0 1-3.37.73 8.15 8.15 0 0 1-8.14-8.1 8.59 8.59 0 0 1 .25-2A1 1 0 0 0 8 2.36a10.14 10.14 0 1 0 14 11.69 1 1 0 0 0-.36-1.05z"/></svg>
    </label>
    <a href="#register" class="btn btn-primary btn-sm md:btn-md"><span class="sm:hidden">খেলুন</span><span class="hidden sm:inline">কুইজ শুরু করুন</span></a>
  </div>
</div>

<script>
  // Theme toggle: night (dark) <-> light, persisted in localStorage
  (function () {
    var toggle = document.getElementById('theme-toggle');
    var current = localStorage.getItem('tb-theme') || 'night';
    document.documentElement.setAttribute('data-theme', current);
    toggle.checked = (current === 'light');
    toggle.addEventListener('change', function () {
      var theme = toggle.checked ? 'light' : 'night';
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('tb-theme', theme);
    });
  })();
</script>
