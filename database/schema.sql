-- QuizJeto database schema (SQLite)
-- Holds only NON-user content: quiz questions + dummy leaderboard players.
-- Real users are NOT stored here (the user's name lives only in the PHP session;
-- number verification/subscription is handled by bdapps).

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

-- Dummy players shown on the leaderboard for social proof (not real users).
CREATE TABLE IF NOT EXISTS dummy_players (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);
