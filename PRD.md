# Spanish Learning App — Product Requirements

**Status:** Draft v1
**Owner:** Chris (Product Owner)
**Last updated:** 2026-06-28

---

## 1. Overview

A personal, web-based Spanish learning app for my kids that combines an **Anki-style spaced-repetition system** with **AI-generated sentence cards**. Unlike a typical flashcard app, the curriculum is built from a library of *concepts* (verbs, conjugations, words, grammar patterns) that I control from an admin panel. The AI generates fresh sentence constructions using **only the concepts the kids have already unlocked**, and grades their answers for being **directionally correct** — comprehension over precision.

### North Star
Get the kids **comfortable reading and understanding Spanish** — able to look at a sentence and grasp its meaning. This is a *comprehension and comfort* tool, not a precision tool. I have other, more traditional tools for exact grammar and spelling.

### One-line summary
> I flip switches in an admin panel to say "these verbs, tenses, words, and patterns are now fair game." The AI builds sentences from them, drops them into each kid's spaced-repetition deck, and forgivingly grades the kids' typed English translations.

---

## 2. Goals & Non-Goals

### Goals
- **Effortless layering.** I add a verb, toggle a tense, or enable a pattern, and the system can immediately generate new cards that respect it. No hand-writing sentences.
- **AI-generated, fenced constructions.** Cards are fresh sentences recombining *known* concepts — never material the kids haven't reached.
- **Forgiving, directional grading.** A kid types the English meaning of a Spanish sentence; the AI judges whether they understood it, forgiving wording, synonyms, and spelling — but holding the line on meaning-critical features (tense, gender, number).
- **Spaced repetition that just works for kids.** No self-rating, no decision fatigue. The AI's verdict drives the schedule automatically.
- **Reading comprehension first.** See Spanish → understand it.

### Non-Goals (v1)
- **Not a vocabulary tool.** Nouns and "random" words are a small, curated supporting cast — not the subject of study.
- **No audio / listening / speaking.** Reading only for now.
- **No precision grading.** Accents, exact synonyms, and word order are intentionally forgiven.
- **No public/multi-family product.** Built for my kids; profiles can be hard-coded.
- **No per-concept mastery scheduling.** Spaced repetition tracks **cards**, not concepts (kept deliberately simple).

---

## 3. Users

| User | Role | What they do |
|------|------|--------------|
| **Me (admin)** | Curriculum owner | Manage the concept library, toggle what's unlocked, generate/refresh/rebuild decks, edit or retire cards, view progress. |
| **The kids** | Learners | Log in, do their daily review session, type English meanings, get instant forgiving feedback. |

**Kid profiles (seeded):**

| Name | Password |
|------|----------|
| Kai | `053018` |
| Malick | `080216` |

- **Auth:** Hard-coded user profiles + passwords are acceptable. Simple login, no signup flow.
- **All concepts are fully admin-managed** — the verbs grid, words library, and patterns are added, edited, and toggled by me in the admin panel. Nothing about the curriculum is hardcoded in the app; the only "seed" values are *which concepts start switched on at first launch* (see §12).

---

## 4. Core Mental Model

Two layers, kept deliberately separate. This separation is the heart of the design.

### Layer 1 — The Concept Library (what *can* be known)
The things I layer on over time. Three flavors:

1. **Verbs** — an infinitive + English meaning, plus a matrix of **which tenses/forms are enabled** for that verb (the verbs grid).
2. **Words** — pronouns, connectors, nouns, adjectives. Each has a **role**: *target* (taught & tested) or *ingredient* (used to flavor sentences, never the point of a card).
3. **Patterns** — global, sentence-level rules that aren't tied to a single verb (e.g. object-pronoun placement, negation, adjective agreement). Toggleable freeform instructions.

### Layer 2 — Cards (what the kids actually see)
AI-generated sentences that **combine** concepts from Layer 1. A card is a *vehicle*; the concept is the *cargo*. Cards can be generated, edited, retired, and replaced without disturbing the library.

### Plus — Per-kid state
Each kid's unlocked concepts and their spaced-repetition schedule, separate from the shared card pool.

### The two key distinctions
- **Column vs. pattern:** If a rule **varies verb-by-verb**, it's a *column in the verbs grid* (present, past, commands, -ing — all forms of a verb). If it's **one rule across all verbs at once**, it's a *pattern switch* (object placement, negation).
- **Target vs. ingredient:** *Targets* are what a card teaches and what grading enforces. *Ingredients* (mostly nouns) make sentences concrete and varied but are never the thing being graded — which is exactly what keeps this from becoming a vocab tool.

---

## 5. Data Model

> Plain-English first: everything the kids can learn is a "concept" with an on/off switch. The AI may only build sentences from switched-on concepts. Cards are generated sentences that reference the concepts they use. Each kid has their own schedule per card.

### 5.1 Verb concept
```json
{
  "id": "verb_estar",
  "type": "verb",
  "spanish": "Estar",
  "english": "to be",
  "tag": "Key Verbs",
  "verb_class": "AR",                 // AR | ER | IR | irregular
  "enabled_tenses": ["infinitive", "present", "past"],  // the "x"s from the verbs grid
  "drill_all_forms": true,            // key verbs: true; long-tail: false
  "unlocked": true
}
```
- `enabled_tenses` is an **open list**, not fixed columns. Future values: `future`, `gerund` (-ing), `imperfect` (the other past), `commands`, `conditional`.
- The (verb × tense) pairs across the whole library form the **generation allowlist** for conjugated forms.
- A conjugated form like `aprendo` is **not** stored as its own concept — it's the product of (`aprender` × `present`), generated on the fly. This matches the teaching philosophy: learn the infinitive + the pattern, absorb conjugations by exposure.

### 5.2 Word concept
```json
{
  "id": "word_porque",
  "type": "word",
  "spanish": "porque",
  "english": "because",
  "category": "connector",            // pronoun | connector | adverb | noun | adjective | question
  "gender": null,                     // for nouns/adjectives where relevant
  "role": "target",                   // target = taught & tested; ingredient = flavor only
  "unlocked": true
}
```
- **Default roles by category** (overridable per word): connectors, pronouns, question words, adverbs → *target*; nouns → *ingredient*; adjectives → admin's choice.
- Keep the ingredient pantry **small and curated — on purpose.** A *core* set of useful nouns. The admin UI shows a live count (e.g. "Ingredient nouns: 47") so I can keep it lean.

### 5.3 Pattern
```json
{
  "id": "pattern_dop",
  "type": "pattern",
  "name": "Direct-object pronoun placement",
  "instruction": "Use direct-object pronouns (lo, la, le...). Prefer them before the verb: 'lo quiero.'",
  "enabled": true
}
```
- Freeform `instruction` text, individually toggleable. Only **enabled** patterns are pasted into the generator's instructions.

### 5.4 Card
```json
{
  "id": "card_8821",
  "source": "ai",                     // ai | manual
  "spanish": "Nosotros fuimos a caminar por la escuela",
  "english": "We went to walk around the school",
  "test_direction": "es_to_en",       // v1: always es_to_en
  "uses_concepts": ["verb_ir", "verb_caminar", "word_por"],
  "must_match": {                      // meaning-critical features grading WILL enforce
    "tense": "past",
    "subject": "1st_plural",
    "gender": null
  },
  "status": "active",                  // active | retired
  "created_at": "2026-06-28"
}
```
- **`must_match` is the heart of forgiving grading.** Grading checks: *meaning preserved?* **and** *does the answer honor every feature in `must_match`?* Everything not listed (exact wording, synonyms, accents, "around" vs "by," ingredient noun choice) is forgiven.

### 5.5 Kid
```json
{ "id": "kid_mateo", "name": "Mateo", "password_hash": "..." }
```

### 5.6 Review state (spaced repetition — per kid, per card)
```json
{
  "kid_id": "kid_mateo",
  "card_id": "card_8821",
  "due": "2026-07-02",
  "interval_days": 6,
  "ease": 2.4,
  "reps": 3,
  "lapses": 1,
  "last_result": "pass",              // from the AI verdict
  "last_reviewed": "2026-06-28"
}
```

---

## 6. Admin Experience (me)

A web admin interface with these sections:

### 6.1 Verbs grid
- Rows = verbs; columns = forms/tenses (open-ended). A checkbox in each cell.
- Add a verb (Spanish, English, class, tag/set). Toggle any (verb × tense) cell.
- Mark `drill_all_forms` for key verbs.

### 6.2 Words library
- Add/edit pronouns, connectors, nouns, adjectives.
- Set `role` (target/ingredient) — defaulted by category, overridable.
- Live ingredient counts to keep the pantry lean.

### 6.3 Patterns
- A short list of named, toggleable freeform rules.
- Each is plain-text instruction passed to the AI when enabled.

### 6.4 Cards
- Browse generated & manual cards. Edit wording, edit `must_match`, retire, or delete.
- Add a manual card if I want a specific sentence.

### 6.5 Generation controls
Three distinct actions (the distinction matters because of progress):

| Button | What it does | Effect on kids' progress |
|--------|-------------|--------------------------|
| **Generate more** | Adds N new cards using the current allowlist + patterns. Everyday button. | **Preserved.** Additive only. |
| **Refresh** | Retires weak/stale cards, keeps the good ones, backfills. | **Preserved** on surviving cards. |
| **Rebuild** | Fresh deck from scratch. | **Wipes schedules.** Clearly labeled as a reset with a confirm step. |

- **Cards auto-publish** — generated cards go straight into the deck (no approval queue). I can prune later via the Cards screen.
- Generation can be **targeted** ("emphasize the preterite," "use these new verbs").

### 6.6 Progress view
- Per kid: cards seen, due today, rough mastery signal, recent misses. Lightweight — enough to answer "are they ready for the next layer?"

### 6.7 Settings & Config
All admin-editable, no code changes required:
- **House style text** — the standing freeform instruction pasted into every generation and grading call (see §9.1). The single source of "voice" and forgiveness rules.
- **Noun pantry** — the curated list of ingredient nouns the generator may sprinkle in. Configurable; kept deliberately small with a live count shown.
- **Daily new-card pace** — how many new cards each kid is introduced per day. Set per kid.
- **Spaced-repetition tuning** — starting intervals, ease, and miss penalty.

---

## 7. Kid Experience

### 7.1 Login
- Pick name, enter password. Simple.

### 7.2 Review session (the core loop)
1. Card shows a **Spanish sentence**.
2. Kid **types the English meaning**.
3. AI grades **directionally** (see §9). Result is **pass** or **needs work**.
4. **On pass:** brief positive confirmation; card scheduled further out.
5. **On miss:** show the correct/accepted English, a one-line gentle nudge if a `must_match` feature was wrong (e.g. "close — this one is *past* tense"); card comes back soon.
6. **No self-rating.** The AI verdict alone drives scheduling (decided: AI grades, auto-schedules).
7. Next card until the day's due cards are done; friendly "all done" state.

### 7.3 Tone
- Encouraging, low-pressure, age-appropriate. Misses are normal and framed as "let's see it again soon," not failure.

---

## 8. AI Generation

When I press **Generate**, the AI receives three deliberately different kinds of input:

1. **The hard allowlist (structured data)** — the exact enabled (verb × tense) pairs and unlocked words. This is the **fence** and must be reliable, so it stays structured, never prose. The AI may not use anything outside it.
2. **Active pattern snippets (freeform)** — the text of every enabled pattern.
3. **House style (freeform, rarely changes)** — standing philosophy: build natural, short, readable sentences; aim for comprehensible everyday constructions; "directionally correct" is the goal.

For each generated card the AI returns: `spanish`, `english`, `uses_concepts`, and a proposed `must_match`. Generation is **on-demand** (not every review) — build a batch, let the kids master it, then layer on and generate more.

### Fencing rules
- Only unlocked verbs, in only their enabled tenses.
- Only unlocked words. Ingredients may be sprinkled in; targets should be exercised meaningfully.
- Only enabled patterns; withheld patterns must not appear.

---

## 9. AI Grading ("Directionally Correct")

The kid sees Spanish and types English. The AI judges **comprehension**, not precision.

### Pass if:
- The **meaning is preserved** (idioms count — e.g. *"Nosotros fuimos a caminar por la escuela"* → "we went to walk around the school" need not be word-for-word).
- **Every `must_match` feature is honored.**

### Forgiven (never fails a kid):
- Spelling and accents.
- Synonyms and natural paraphrase ("around" vs "by," "talk" vs "speak").
- Word order.
- Ingredient noun choices.

### Must be correct (meaning-critical — enforced via `must_match`):
- **Gender** (feminine vs masculine where it changes meaning).
- **Tense** (present vs past, etc.).
- **Number / person** (I vs we vs they).

### Output
- A verdict (`pass` / `needs_work`), the accepted English, and an optional one-line nudge naming the missed feature. The **house style text is reused** here so the grader's forgiveness matches the generator's intent.

### 9.1 House style (shared by generation & grading)
The **house style** is a short, admin-editable paragraph that defines the app's voice and forgiveness rules in plain English. It is pasted into **every** AI call — both generation and grading — so the two always agree on what "directionally correct" means. Written once, edited anytime in Settings (§6.7). Example:

> *"Build short, natural, everyday sentences a child can read and relate to. Favor comprehension over complexity. When grading a kid's English, be encouraging and forgiving: ignore spelling, accents, synonyms, and word order. Only require that they got the meaning right, plus the correct tense, gender, and number."*

---

## 10. Spaced Repetition

- **Card-level scheduling** (not concept-level) — deliberately simple.
- Algorithm: a lightweight **SM-2-style** scheme (interval + ease), or similar.
  - **Pass** → interval grows (× ease).
  - **Miss** → interval resets to a short value; small ease penalty; card returns soon.
- **No self-rating.** The AI's pass/needs-work verdict is the only input.
- New cards enter as "new" and are introduced at a sane daily pace.
- Trade-off accepted: rebuilding the deck wipes schedules (see §6.5).

---

## 11. Technical Approach

- **Framework:** **Laravel** (PHP), hosted on **Laravel Cloud** (decided). Kids log in from any device; progress synced in the cloud.
- **Frontend:** **Livewire 3** + **Flux UI** + Alpine.js + Tailwind CSS. Laravel-native, single-language, the default Laravel starter kit, and deploys on Laravel Cloud with no special config. Two faces in one app: kid review UI + admin panel.
  - *Alternative considered:* Inertia + Vue for a more SPA-like feel. Deferred — adds a JS build layer this app doesn't need; revisit only if the kid review screen ever feels sluggish.
- **AI:** Claude (latest model) for both generation and grading, called **server-side** from Laravel (HTTP client / official SDK). The API key lives in the backend env, never in the browser.
- **Storage:** a managed database (Postgres on Laravel Cloud) for concepts, cards, kids, and review state.
- **Auth:** minimal — hard-coded/seeded profiles + passwords (§3).
- **Assets:** Vite build (handled automatically in the Laravel Cloud deploy pipeline).

### Build phasing
- **Phase 1 (MVP):** concept library + verbs grid; manual + AI card generation (auto-publish); kid login; review loop (see Spanish → type English); AI grading; card-level spaced repetition.
- **Phase 2:** patterns section; refresh/rebuild controls; progress view; targeted generation.
- **Phase 3 (later, out of v1 scope):** audio/listening, English→Spanish production mode, richer analytics.

---

## 12. Initial Setup Values (set once in admin)

> These are **not** code constraints — every item below is admin-configurable (§6.7). They're just the starting values to enter during first-time setup.

- **Kids' ages** — to tune sentence length / reading level. *(Names & passwords: done — see §3.)*
- **House style text** — the final standing instruction for generation + grading (§9.1).
- **Initial noun pantry** — the core ingredient nouns to start with (~30–50).
- **Starting unlocked set** — which verbs/tenses/words/patterns are switched on at launch (likely Key Verbs + Verb Set 1 infinitives + a few connectors, mirroring spreadsheet Week 1–2).
- **Daily new-card pace** per kid.
- **Spaced-repetition tuning** — starting intervals, ease, miss penalty.

---

## 13. Appendix — Seed Data (from current spreadsheet)

The existing **Spanish Plan** spreadsheet is the seed for the concept library:

- **Verbs page** → the verbs grid. Columns *Present / Past / Infinitive* map to `enabled_tenses`; *Tag* (Key Verbs, Verb Set 1–4) maps to `tag`. ~80 verbs already listed.
- **Vocab Month 1 & 2 page** → existing constructions and word lists, useful as:
  - Examples of the *style* of sentence to generate at each level.
  - Source for the **words library**: Pronouns Practice, Random Useful Words (connectors/adverbs), Characteristics (adjectives).
- **Progression** (columns left→right): Primary Verbs → Verb Sets + conjugations → Verb Phrases → Pronouns → Random Useful Words → Combined Reinforcement → Past Tense → Direct Object Placement → Commands → Gerunds → Characteristics. This is the natural **unlock order** for layering.

### Teaching philosophy captured (for the house-style text)
- Verbs first; nouns are easy and can come later.
- Master a small core of key verbs' conjugations; learn everything else as infinitives and absorb conjugations by exposure.
- Layer continuously; reinforcement (recombining the known) over constant new material.
- The key verbs are the *chassis* that carry infinitives (`voy a ___`, `quiero ___`, `tengo que ___`, `puedo ___`, `necesito ___`).
- Once ~150 infinitives are solid, gradually turn on present-tense conjugation for batches of them (not all at once, and not as drilled forms).
