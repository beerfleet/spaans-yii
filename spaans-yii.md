# Spaans Oefenen — Yii2 app

**Root:** `C:\xampp\htdocs\spaans-yii`
**Stack:** Yii2 2.0.54 (PHP ≥8.2), MySQL `spaans` (utf8mb4), Bootstrap5, Symfonymailer.
**UI language:** `nl-NL`. App name from `params.appName`. Login exists but nav login commented out (single-user-ish).

## Purpose
Dutch↔Spanish vocabulary trainer. Manage Spanish words + Dutch translations, group them into "lijsten" (lists), bulk-import, translate in bulk, practice in either direction (NL→ES / ES→NL), track per-word statistics, detect duplicates/homonyms, split multi-variant rows.

## Data model
- **chapter** (`id`, `name`, `description`, `created_at`, `updated_at`) — `number` column dropped.
- **word** (`id`, `spanish` NOT NULL, `dutch` nullable, `created_at`, `updated_at`) — `chapter_id` dropped; lists are M-N.
- **chapter_word** (`word_id`, `chapter_id`) composite PK; FKs to both with CASCADE.
- **word_statistic** (`id`, `word_id` FK cascade, `correct_count`, `incorrect_count`, `nl_to_sp` bool, `last_practiced_at`).
- **log** (Yii log table). `User` identity model exists for login.

Relations: `Word::getChapters()` M-N via junction; `Word::getWordStatistic()` hasOne; `Chapter::getWords()` M-N. `Word::afterSave()` deletes+reinserts junction rows from `chapterIds`.

## Routing (config/web.php `urlManager`)
Pretty URLs on, strict parsing off. Dutch aliases:

### Chapters (hoofdstuk)
- `hoofdstuk` → `chapter/index`
- `hoofdstuk/maak` → `chapter/create`
- `hoofdstuk/wijzig/<id:\d+>` → `chapter/update`
- `hoofdstuk/bekijk/<id:\d+>` → `chapter/view`
- `hoofdstuk/beheer/<id:\d+>` → `chapter/manage-words`
- `hoofdstuk/verwijder-woord/<id:\d+>/<word_id:\d+>` → `chapter/remove-word`
- `hoofdstuk/wis/<id:\d+>` → `chapter/delete`
- (also `chapter/index` → `chapter`)

### Words (woord)
- `woord` → `word/index`
- `woord/hs/<chapter_id:\d+>` → `word/index-by-chapter`
- `woord/maak` → `word/create`
- `woord/maak/bulk` → `word/create-multiple`
- `woord/wijzig/<id:\d+>` → `word/update`
- `woord/bekijk/<id:\d+>` → `word/view`
- `woord/wis/<id:\d+>` → `word/delete`
- `woord/onvertaald` → `word/list-untranslated`
- `woord/dubbelen` → `word/duplicates`
- `woord/splitsen/<id:\d+>` → `word/split`
- `woord/bulk-vertaal` → `word/bulk-translate`
- `woord/bulk-toewijzen` → `word/bulk-assign`

### Practice (oefenen)
- `oefenen` → `practice/start`
- `oefenen/oefening` → `practice/practice`
- `oefenen/woord/<id:\d+>` → `practice/practice-word`
- `oefenen/resultaat` → `practice/result`
- `oefenen/statistieken` → `practice/stats`
- `oefenen/statistieken/wis/<id:\d+>` → `practice/reset-stat`

## Controllers / actions

**SiteController**: `index`, `login`, `logout`, `contact`, `about`, `error`, `captcha`. Index shows counts (words, chapters, untranslated, listless, duplicate groups/words) + active practice session progress.

**WordController**: `index`, `index-by-chapter`, `view`, `list-untranslated`, `create`, `create-multiple`, `update`, `delete`, `duplicates`, `split`, `bulk-translate`, `bulk-assign`. VerbFilter POST: delete, bulk-translate, bulk-assign, split.

**ChapterController**: `index`, `view`, `create`, `update`, `delete`, `manage-words`, `remove-word`. VerbFilter POST: delete, remove-word.

**PracticeController**: `start`, `practice`, `practice-word`, `stop`, `result`, `repeat`, `stats`, `reset-stat`. VerbFilter POST: repeat, stop, practice-word, reset-stat.

## Key workflows / features

1. **Bulk add** (`create-multiple`): paste one expression per line → preview (flags new/duplicate/exists, accent-variant hints) → confirm checked rows. Lists selected apply to all new words; unchecked rows skipped. Redirects to untranslated list.

2. **Bulk translate** (`list-untranslated` + `bulk-translate`): inline Dutch input + multi-list select per row; save all on page. `returnUrl` support. Lists replace per word.

3. **Word management** (`index`): inline Dutch + multi-list per row, filter by list (`chapter_id`: 0 = no list), checkbox selection + assign to list (`bulk-assign`; `none` clears all lists). List-less words get "ook in:" suggestions based on shared Spanish/Dutch forms.

4. **Practice**: start form selects lists/all, max words, direction, difficult-first, only-unpracticed. Builds deduped prompt list (homonyms by prompt deduped), shuffled or difficult-first by misses. Session in `practice` session key. Practice loop shows one random variant of comma alternatives; answer accepted if any homonym counterpart matches; exact vs accent-lenient (ñ stays strict). Updates `WordStatistic` per word+direction. Result page with per-row review; repeat wrong words.

5. **One-word practice** from `word/view` (POST dir nl/sp).

6. **Stats** (`practice/stats`): filter by list (0 = no list) and direction; summary + grid; reset stat per word+direction.

7. **Duplicates** (`word/duplicates`): groups by accent-lenient terms (homonyms, exact dupes, overlapping comma parts, shared translations); multi-variant rows section with split action (keeps Dutch/lists, drops old stats).

8. **Homonyms**: `word/view` shows other rows sharing Spanish form; practice accepts any counterpart.

## Conventions / quirks
- `WordSearch extends Word` but overrides `scenarios()` to `Model::scenarios()` (bypasses parent).
- `Word` scenarios: default (requires spanish+dutch), `bulkCreate`, `bulkForm`, `bulkTranslate` (lenient).
- `chapterIds` property lazy-loads junction; `afterSave` deletes+reinserts junction rows.
- Accent handling: `Word::normalizeAnswer()` strips accents except ñ/Ñ (kept strict).
- Practice session stored in session; `practiceResult` stored separately on completion.
- `PracticeSelection::load()` normalizes `chapters` (string→array) and empty `max_words` (→0).
- VerbFilter + POST for destructive/bulk actions.
- TimestampBehavior on Word/Chapter.
- SiteController uses constructor DI for MailerInterface + Security.
- `_accent-keys` partial provides Spanish accent buttons.
- `Word::suggestLists()` does two queries regardless of page size.
- `Word::getPracticeAnswers()` is homonym-aware (all rows sharing prompt).
- `Word::findMultiVariantRows()` finds rows with comma-separated variants.
- `Word::findDuplicateGroups()` groups by accent-lenient terms including comma parts and shared translations.
- List-less words: filter value `0` means "no list" (NOT EXISTS on junction).
- `Word::pickPromptVariant()` picks one random variant of comma alternatives for display.
- `WordStatistic::last_practiced_at` tracks recency per word+direction.
- `Word::getListsText()` returns comma-separated list names or null.