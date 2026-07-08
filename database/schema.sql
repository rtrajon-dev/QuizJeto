-- QuizJeto database schema (SQLite)
-- Holds quiz questions + REAL player accounts and their game results (leaderboard).
-- The raw MSISDN is never stored: users are keyed by a sha256 hash of the number,
-- with only a masked form (017•••678) kept for display. Subscription/number
-- verification is still handled by bdapps.

CREATE TABLE IF NOT EXISTS questions (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    topic          TEXT NOT NULL,
    question       TEXT NOT NULL,
    option_a       TEXT NOT NULL,
    option_b       TEXT NOT NULL,
    option_c       TEXT NOT NULL,
    option_d       TEXT NOT NULL,
    correct_option TEXT NOT NULL CHECK (correct_option IN ('a','b','c','d')),
    difficulty     TEXT NOT NULL DEFAULT 'medium',
    created_at     TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Real players. Identity key is a hash of the phone number (no raw MSISDN stored).
CREATE TABLE IF NOT EXISTS users (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    phone_hash   TEXT NOT NULL UNIQUE,     -- sha256(phone) — identity key
    phone_masked TEXT NOT NULL,            -- 017•••678 — safe to display
    display_name TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

-- One row per finished quiz game — the leaderboard reads from here.
-- played_at uses localtime so PHP's date() and SQLite's date('now','localtime')
-- agree when filtering "today" / "this week".
CREATE TABLE IF NOT EXISTS quiz_results (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id   INTEGER NOT NULL REFERENCES users(id),
    score     INTEGER NOT NULL,
    total     INTEGER NOT NULL,
    played_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
);

CREATE INDEX IF NOT EXISTS idx_results_played ON quiz_results(played_at);
CREATE INDEX IF NOT EXISTS idx_results_user   ON quiz_results(user_id);
