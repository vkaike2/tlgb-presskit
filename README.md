# The Last Good Boy — Press Kit

A [presskit()](https://dopresskit.com/) press page for **The Last Good Boy**, rendered to
static HTML so it can be hosted on GitHub Pages.

## Why there is a build step

presskit() is a PHP application: `index.php` and `sheet.php` read your `data.xml` files and
render the pages on each request. GitHub Pages serves static files only and never runs PHP,
so instead of deploying the PHP we run it **once locally** and commit the HTML it produced.

`tools/build.mjs` does that: it starts PHP's built-in server against `src/`, fetches every
page, rewrites the internal `sheet.php?p=…` links to plain `.html` filenames, copies the
assets, and writes the result to `docs/`.

The build pins **PHP 7.4**, not 8.x. presskit()'s code predates PHP 8 and hits fatal errors
there (`count()` on non-countable values). On Windows the script downloads a portable PHP 7.4
into `tools/php/` the first time you run it; that folder is gitignored.

## Layout

```
src/                        presskit() sources + your content  ← edit here
  data.xml                    the studio / company page
  the_last_good_boy/
    data.xml                  the game page
    images/                   header, logo, icon, screenshots
  images/                     studio logo & images
  index.php sheet.php …     presskit() itself — see "Local changes to presskit()"
tools/build.mjs             renders src/ → docs/
docs/                       generated static site            ← never edit, it is overwritten
```

Each subdirectory of `src/` containing a `data.xml` becomes one game page. The directory name
is both the URL and the display label, with underscores turned into spaces — hence
`the_last_good_boy` → `the_last_good_boy.html`, shown as "The Last Good Boy".

## Editing and building

1. Edit the `data.xml` files in `src/`.
2. Run the build:
   ```
   node tools/build.mjs
   ```
3. Preview `docs/index.html` in a browser, then commit both `src/` and `docs/`.

## Publishing to GitHub Pages

In the repository's **Settings → Pages**, set the source to **Deploy from a branch**, branch
`main`, folder `/docs`. Every push that includes a rebuilt `docs/` updates the live page.

## Languages

The kit is built in English (default) and Brazilian Portuguese. presskit() normally switches
language with a `?l=pt` query parameter, which needs PHP — so the build instead renders every
page once per language and rewrites the switcher to point at static files:

| Page | English | Português |
|---|---|---|
| Studio | `index.html` | `index-pt.html` |
| Game | `the_last_good_boy.html` | `the_last_good_boy-pt.html` |

The dropdown in the sidebar appears automatically as soon as `src/lang/` holds more than one
file, and navigating between pages keeps the current language.

Translation lives in two separate places:

- **Interface chrome** ("Factsheet", "Release date:", …) — `src/lang/pt-Português.xml`.
  The filename is the format presskit() parses: `<code>-<Display Name>.xml`, so the part
  before the first `-` becomes the URL code and the rest is the dropdown label.
- **Your actual content** — `src/data-pt.xml` and `src/the_last_good_boy/data-pt.xml`.

**These content files are full copies, not overlays.** presskit() does not fall back to the
English `data.xml` field by field: if a tag is missing from `data-pt.xml`, it is simply absent
from the Portuguese page. When you edit `data.xml`, edit its `-pt` twin in the same pass.

To add a language, copy `src/lang/en-English.xml` to `src/lang/<code>-<Name>.xml`, translate
every `<local>` value, and add matching `data-<code>.xml` files. Any string you leave
untranslated **renders in red on the page** — that is presskit()'s deliberate way of showing
you the gaps, not a bug.

## Local changes to presskit()

`src/` is a lightly patched copy of upstream presskit(), not a pristine one. Every edit is
marked with a `LOCAL CHANGE` comment, so `grep -rn "LOCAL CHANGE" src/` lists them all.
**If you ever re-download `archive.zip` from upstream, these get overwritten** — re-apply them.

Currently there is one: **an optional `label` attribute on links.** Upstream prints the full
URL as the link text, and a long URL is a single unbreakable word, so it pushed out of the
sidebar column and collided with the content next to it. With a label you control the text:

```xml
<website label="Steam page">store.steampowered.com/app/3524950/The_Last_Good_Boy/</website>
```

The attribute is honoured on `<website>` and on `<link>` inside `<contacts>` — those are the
only two places upstream uses the URL as its own link text. It is deliberately *not* read
elsewhere: `<platform>` and `<social>` already display their `<name>`, and `<additional>`
shortens the URL to just the host. Omit the attribute and you get the old behaviour.

`style.css` also carries an `overflow-wrap: break-word` rule as a safety net, so any link that
still shows a raw URL wraps instead of overflowing.

## Content gotchas

These are presskit() behaviours that silently produce wrong output:

- **Links must not include a scheme.** Write `store.steampowered.com/app/3524950/`, not
  `https://store.steampowered.com/…`. presskit() prepends `https://` itself, so a full URL
  renders as the broken `https://http://…`. The bundled example files get this wrong.
- **Screenshots must be `.png` or `.gif`.** `.jpg` files in an `images/` folder are ignored
  entirely.
- **Image filenames are meaningful.** In `src/<game>/images/`:
  | File | Role |
  |---|---|
  | `header.png` | Banner across the top of the page |
  | `logo.png`, `icon.png` | Shown in the "Logo & Icon" section |
  | anything else `.png`/`.gif` | Treated as a screenshot |
  | `capsules/` | Steam capsule art, its own section (see below) |
  | `images.zip`, `logo.zip` | Optional "download all" bundles |
- **Capsules are a local addition**, not an upstream presskit() feature. Drop Steam
  capsule art into `src/<game>/images/capsules/` and it appears under its own
  "Capsules" heading with its own nav entry. Unlike the screenshot grid, this
  folder **does** accept `.jpg`/`.jpeg` as well as `.png`/`.gif`, because Steam
  capsule exports are usually JPEG. Files are sorted alphabetically, so name them
  in the order you want them shown. An optional `capsules.zip` in that folder adds
  a "download all" button. The strings live in `src/lang/*.xml` like any other
  heading.
- **Omitting a tag is not the same as leaving it empty.** A missing `<phone>` makes the page
  print the literal text `COMPANY_PHONE`. Keep the tag and leave it empty instead.
- **Only YouTube and Vimeo embed.** Steam-hosted trailers cannot be used; upload the trailer
  to YouTube and use its 11-character video ID.

`src/_data.company.example.xml` and `src/_data.game.example.xml` are the untouched upstream
templates, kept for reference on tags not currently used (awards, quotes, press copies).
