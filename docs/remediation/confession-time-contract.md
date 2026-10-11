# Confession Time Contract

Updated: 2026-10-09, Africa/Harare.

Bushisa stores confession posting time in `confessions.created_at`, set by the database when the confession is created. Application responses must serialize that stored value, not the current PHP request time.

Current implementation:

- `api/confessions.php` inserts with `NOW()`, reloads `created_at` from the inserted row and returns `created_at` as a UTC ISO-8601 string.
- GET feed responses serialize each stored `created_at` through `bushisa_utc_iso()`.
- `confessions.php` renders `<time datetime="...">` using the same UTC ISO value and displays relative text via `format_time_ago()`.
- Invalid or missing timestamps render as an empty string rather than pretending the post is current.

Synthetic acceptance completed:

- Browser test verifies that an older persisted confession timestamp does not display as "Just now" after a clean rendered feed load.

Remaining acceptance work:

- Fake-clock boundary tests for minutes, hours, yesterday and older posts.
- Human H09 comparison against the selected X-style display contract.
