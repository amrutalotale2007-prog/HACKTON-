# SkillBridge — what was wrong, and what I fixed

## The big blocker: `config/database.php` and `includes/auth.php` didn't exist
13 of your pages start with `require_once "config/database.php"` (and two also
require `includes/auth.php`), but neither file was in the upload. Every one of
those pages was fatal-erroring with *"Failed opening required..."* before any
of their own logic ever ran:

applications, candidate-matching, collaboration, industry-dashboard,
opportunities, post-opportunity, research-projects, skill-assessment,
skill-gap, skill-profile.

**Fixed:** added both files. `config/database.php` opens the shared mysqli
connection (same host/user/pass/db your login/signup used). `includes/auth.php`
starts the session and redirects to login if nobody's logged in. Update the
four constants in `config/database.php` to your real MySQL credentials.

## `index.php` wasn't your project at all
This one's worth knowing about: the `index.php` you uploaded was **WampServer's
own default control-panel page** (the one that lists installed projects/aliases
and reads `../wampmanager.conf`). It has nothing to do with SkillBridge, and it
calls `die()` if that config file isn't found — which it never would be outside
your local Wamp install. I replaced it with an actual SkillBridge homepage
(hero section + login/signup CTAs, redirects logged-in users straight to their
dashboard).

## Login was two separate bugs, front and back
- **Frontend:** the login form's `<input>` fields had no `name` attributes, the
  form had no `method`/`action`, and it called `onsubmit="loginUser(event)"` —
  a function that doesn't exist anywhere in `script.js`. So even a correct
  password would never actually reach the PHP code below it.
- **Backend:** it queried `SELECT id, full_name, ... FROM users`, but the
  columns used everywhere else in your app are `id` and `name` (see schema
  note below) — full_name doesn't exist. It also stored the session as
  `full_name`, while dashboards expect `name`, and as `role` while
  `research-projects.php` was checking for `user_role`.

**Fixed:** form now submits properly, query uses the real column names,
session keys are standardized to `user_id` / `name` / `email` / `role`, and
login now redirects to the right dashboard per role (academician / industry /
student-institution) instead of a hardcoded `student-dashboard.php`.

## Signup: same column mismatch, plus a case mismatch
`signup.php` inserted into `(full_name, ...)` — same nonexistent column — and
stored the role as `"Student"`, `"Industry"`, etc., while the `role` column
and every permission check in the app (`=== 'academician'`) expect lowercase.

**Fixed:** inserts into `name`, lowercases the role before validating/storing,
session key changed to `name`.

## Two dashboards were reading session keys nothing ever sets
- `academician-dashboard.php` read `$_SESSION['academician_name']` — always
  blank — **and had no login check at all**, so the URL was open to anyone.
- `ai-recommendation.php` read `$_SESSION['student_name']` — same problem, also
  no login check.

**Fixed:** both now require login (and, for the academician page, the right
role) via `includes/auth.php`, and read `$_SESSION['name']`.

## `research-projects.php`'s access check was checking a key that doesn't exist
It gated the whole page on `$_SESSION['user_role'] === 'academician'`, but
nothing sets `user_role` (login sets `role`). Every academician was being
bounced out of their own research page. Fixed to use the role it already
fetches fresh from the database a few lines above.

## A broken internal link
`industry-dashboard.php` linked to `industry-opportunities.php`, which doesn't
exist — the real file is `opportunities.php`. Fixed.

## `script.js` had a "delete anything that says Amruta" hack
There was a block that walked *every element on every page* and blanked any
text matching a hardcoded demo name, and deleted any element with classes like
`.profile-name`/`.user-name`/`.student-name` — globally, on every page load.
That also means it was deleting the *real* logged-in user's name anywhere
those classes are legitimately used. I removed the block rather than keep
papering over it. The actual fix is wherever that name is hardcoded in a
template (not among the files I reviewed here — likely a student dashboard
file that wasn't uploaded) — swap it for `<?= htmlspecialchars($_SESSION['name']) ?>`.

## `schema-fixes.sql` — one real schema disagreement
Your SQL dump created `users.user_id`, but essentially the entire app queries
`users.id`. I standardized on `id` (it's what most of your code already
expects) — run `schema-fixes.sql` against your database to rename the column
(or recreate the table fresh using the version at the bottom of that file).

---

## Round 2 — built the missing blueprint pages, and found a bigger schema issue

You shared your blueprint (Login/Signup → Student/Industry/Academician →
... → Digital Portfolio). Two pages that flow depends on were referenced
everywhere but never existed:

- **`dashboard.php`** — the student hub. Six different pages already link to
  it (`skill-assessment.php`, `skill-gap.php`, `skill-profile.php`,
  `opportunities.php`, `applications.php`, `ai-recommendations.php`), but it
  was never built, so every one of those links 404'd. Built it: shows latest
  assessment score, skills added, application counts by status, and recent
  opportunities, with quick links into the rest of the flow.
- **`portfolio.php`** — the last step of your blueprint. Nothing in the
  project ever queried a portfolio, project, or certification table before
  this. Built a full page: editable headline/about/resume link, an "add
  project" form with a list + remove button, and the same for certifications.
- **`logout.php`** — linked from 12 places across the app, never existed.
  Added it (clears the session, redirects home).
- **Renamed `ai-recommendation.php` → `ai-recommendations.php`.** Every single
  link to it across the whole app (8 of them) used the plural — the file was
  singular. Renaming the file was less error-prone than editing 8 links.

### The bigger find: your database schema has drifted from the SQL dump
While wiring up `dashboard.php` and `portfolio.php` I had to check what
columns every existing query actually expects — and it turns out your **app
code and your SQL dump describe two different schemas.** The dump (and my
earlier `schema-fixes.sql`) assumed a "junction table" design: a student's
data hangs off `student_profiles.student_id`, a company's off
`companies.company_id`, etc. Your actual code doesn't do that anywhere. Every
table your pages query is flatter — `skills`, `assessments`, `opportunities`,
`research_projects`, and `collaborations` all just store a `user_id` (or, in
`applications`, a confusingly-named `student_id` that still holds a plain
`users.id` value) directly. `student_profiles` itself uses different column
names than the dump too (`year`/`location` instead of `year_of_study`/`phone`/
`career_goal`), and a `research_projects` table is used constantly but isn't
in the dump at all.

**I wrote `schema.sql`** — a full, correct schema reverse-engineered from every
query in the codebase, including the new `portfolios`/`projects`/
`certifications` tables. **Use this instead of the original dump and instead
of `schema-fixes.sql`.** If you're on a dev database with no real data yet,
easiest path is `DROP DATABASE skillbridge;` then run `schema.sql` fresh. If
you already have real user data you need to keep, tell me and I'll write
`ALTER TABLE` statements instead.

### One structural gap I found but didn't fix yet
Nothing in the project — not signup, not anywhere — ever `INSERT`s a row into
`student_profiles`. So even after the schema fix, `skill-profile.php`'s
college/course/bio section (and anything else keyed off that table) has
nothing to read for any student. Worth deciding: should signup auto-create an
empty `student_profiles` row for new students, or should `skill-profile.php`
create one the first time someone saves their profile? Say the word and I'll
wire it up.

### Not built: the Academician "Find FDP" branch
Your blueprint's academician path is Find FDP → Research → Collaboration. Only
"Research" and "Collaboration" exist (`research-projects.php`,
`collaboration.php`) — "Find FDP" has no page and no table backing it at all;
it's a dead `href="#"` link in the sidebar. This is a new feature, not a bug
fix, so I didn't build it blind — want me to add it (a `faculty-opportunities.php`
page + table, matching the FDP/Industrial Training/Consultancy categories from
your original dump)?

## What I did *not* rewrite, and why

**The stylesheet doesn't match the markup — badly.** Your PHP pages reference
362 distinct CSS classes; `style.css` only defines 169 of them. Things like
`.auth-wrapper`, `.auth-brand`, `.auth-box`, `.dashboard-hero`, `.hero-badge`,
`.btn-purple`, `.candidate-card`, `.collab-card`, `.assessment-card` and roughly
270 others are used in your templates but have no rules at all — meaning large
sections of nearly every page (including login/signup, which I just fixed
functionally) are currently rendering unstyled. This isn't a quick fix I could
safely guess my way through without knowing your intended visual design, and
it's a genuinely large task (effectively rebuilding the layout CSS for auth
pages, the shared dashboard shell, candidate matching, collaboration,
assessments, and more). I'd rather tackle that with you deliberately, page by
page, than hand you hundreds of lines of invented CSS in one shot. Let me know
if you want to start there next.

**Several linked pages don't exist yet**, and I didn't invent them:
`portfolio.php`, `messages.php`, `projects.php`, `workshops.php`,
`dashboard.php` (the student dashboard — login now redirects students here).
`portfolio.php` was in your file list but never actually made it into the
upload — worth re-uploading if you have it.

**I renamed the re-uploaded files back to their normal names**: `login (1).php`
→ `login.php`, `skill-profile (1).php` → `skill-profile.php`.

**The larger business-logic files** (`candidate-matching.php`,
`collaboration.php`, `post-opportunity.php`, `opportunities.php`,
`applications.php`, `skill-gap.php`, `industry-dashboard.php`) were checked for
the same class of bug (wrong table/column names, wrong session keys, dangling
links) and came back consistent with the `id`/`name` convention — but I didn't
do a full line-by-line logic audit of their SQL, given their size. If something
in one of those still misbehaves once the database is wired up, tell me which
page and I'll dig into that file specifically.
