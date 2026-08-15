#!/usr/bin/env node
// Renders the presskit() PHP sources in src/ into a static site in docs/.
//
// presskit() is PHP, GitHub Pages is static-only, so we run PHP once here and
// keep the HTML it produces. PHP 7.4 is deliberate: the upstream code predates
// PHP 8 and hits fatals there (count() on non-countables).
//
//   node tools/build.mjs

import { spawn, spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const SRC = path.join(ROOT, 'src');
const OUT = path.join(ROOT, 'docs');
const PHP_DIR = path.join(ROOT, 'tools', 'php');
const PHP_URL = 'https://windows.php.net/downloads/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip';
const PORT = 8791;
// presskit() hardcodes 'en' first in its language list and treats it as the default.
const DEFAULT_LANG = 'en';

// Assets presskit() serves alongside the generated pages.
const COPY_AT_ROOT = ['style.css'];
const COPY_DIRS = ['images', 'trailers'];

const log = (m) => console.log(m);

/** Locate a PHP 7.x binary, downloading a portable one on Windows if needed. */
async function ensurePhp() {
	const local = path.join(PHP_DIR, 'php.exe');
	if (existsSync(local)) return local;

	// CI (ubuntu) and anyone with php already on PATH.
	const onPath = spawnSync('php', ['-v'], { encoding: 'utf8' });
	if (onPath.status === 0) {
		const major = /PHP (\d+)\./.exec(onPath.stdout)?.[1];
		if (major === '7') return 'php';
		log(`! php on PATH is PHP ${major}.x; presskit() needs 7.x`);
	}

	if (process.platform !== 'win32') {
		throw new Error('No PHP 7.x found. Install php7.4-cli (with ext-simplexml) and retry.');
	}

	log('Downloading portable PHP 7.4 (one time, ~25MB)...');
	await fs.mkdir(PHP_DIR, { recursive: true });
	const zip = path.join(PHP_DIR, 'php.zip');
	const res = await fetch(PHP_URL);
	if (!res.ok) throw new Error(`PHP download failed: HTTP ${res.status}`);
	await fs.writeFile(zip, Buffer.from(await res.arrayBuffer()));
	const unzip = spawnSync('powershell', [
		'-NoProfile', '-Command',
		`Expand-Archive -LiteralPath '${zip}' -DestinationPath '${PHP_DIR}' -Force`,
	], { encoding: 'utf8' });
	if (unzip.status !== 0) throw new Error(`Unzip failed: ${unzip.stderr}`);
	await fs.rm(zip, { force: true });
	return local;
}

/** Every subdirectory of src/ holding a data.xml is a game sheet. */
async function findGames() {
	const entries = await fs.readdir(SRC, { withFileTypes: true });
	const games = [];
	for (const e of entries) {
		if (!e.isDirectory() || e.name.startsWith('_') || COPY_DIRS.includes(e.name) || e.name === 'lang') continue;
		if (existsSync(path.join(SRC, e.name, 'data.xml'))) games.push(e.name);
	}
	return games.sort();
}

/** Static filename for a page in a given language; the default language gets the bare name. */
function pageName(base, lang) {
	return lang === DEFAULT_LANG ? `${base}.html` : `${base}-${lang}.html`;
}

/**
 * Point presskit()'s internal links at the static filenames we emit.
 * Everything else it generates (anchors, CDN, external links) is already static.
 */
function staticify(html) {
	return html
		// Order matters: the ?l= forms must be consumed before the bare ones.
		.replace(/href="sheet\.php\?p=([^"&]+)&l=([a-zA-Z_]+)"/g, (_, p, l) => `href="${pageName(p, l)}"`)
		.replace(/href="sheet\.php\?p=([^"&]+)"/g, 'href="$1.html"')
		.replace(/href="index\.php\?l=([a-zA-Z_]+)"/g, (_, l) => `href="${pageName('index', l)}"`)
		.replace(/href="index\.php"/g, 'href="index.html"')
		.replace(/href="\."/g, 'href="index.html"')
		// The language <select> navigates via JS to a PHP URL. Rebuild the target
		// from the chosen language code, matching pageName()'s naming.
		.replace(
			/document\.location = 'index\.php\?l='\s*\+\s*this\.value;/,
			`document.location = this.value === '${DEFAULT_LANG}' ? 'index.html' : 'index-' + this.value + '.html';`,
		)
		.replace(
			/document\.location = 'sheet\.php\?p=([^'&]+)&l='\s*\+\s*this\.value;/g,
			(_, p) => `document.location = this.value === '${DEFAULT_LANG}' ? '${p}.html' : '${p}-' + this.value + '.html';`,
		);
}

/**
 * presskit() emits no favicon, so browser tabs fall back to a blank sheet.
 * Add the tags ourselves; the images ride along in src/images, which we copy.
 */
function addFavicon(html) {
	if (!html.includes('</head>') || html.includes('rel="icon"')) return html;
	return html.replace(
		'</head>',
		'\t<link rel="icon" type="image/png" href="images/favicon.png">\n'
		+ '\t\t<link rel="apple-touch-icon" href="images/apple-touch-icon.png">\n\t</head>',
	);
}

/** Language codes come from the lang/<code>-<Name>.xml files presskit() scans. */
async function findLanguages() {
	const files = await fs.readdir(path.join(SRC, 'lang'));
	const codes = files
		.filter((f) => f.endsWith('.xml'))
		.map((f) => f.slice(0, -4).split('-', 1)[0]);
	// English is presskit()'s hardcoded default and must stay first.
	return [DEFAULT_LANG, ...codes.filter((c) => c !== DEFAULT_LANG).sort()];
}

/** Replace the empty .uk-grid shell and its loader script with the real markup. */
function inlineCredits(shell, body) {
	const withBody = shell.replace(
		/<div class="uk-grid">\s*<\/div>/,
		`<div class="uk-grid">\n${body}\n\t\t\t</div>`,
	);
	if (withBody === shell) throw new Error('credits.html shell changed shape; inlining failed');
	return withBody.replace(/<script type="text\/javascript">[\s\S]*?<\/script>/, '');
}

async function fetchPage(url) {
	const res = await fetch(url);
	const html = await res.text();
	if (!res.ok) throw new Error(`${url} -> HTTP ${res.status}`);
	// PHP renders errors into a 200 body, so inspect the HTML itself.
	const bad = /<b>(Fatal error|Parse error|Warning)<\/b>|Uncaught \w*Error/.exec(html);
	if (bad) throw new Error(`${url} rendered a PHP ${bad[1] ?? 'error'}:\n${html.slice(0, 800)}`);
	return addFavicon(staticify(html));
}

async function waitForServer(base) {
	for (let i = 0; i < 60; i++) {
		try {
			await fetch(base);
			return;
		} catch {
			await new Promise((r) => setTimeout(r, 250));
		}
	}
	throw new Error('PHP dev server never came up');
}

async function copyIfPresent(from, to) {
	if (!existsSync(from)) return false;
	await fs.cp(from, to, { recursive: true });
	return true;
}

async function main() {
	if (!existsSync(path.join(SRC, 'data.xml'))) {
		throw new Error('src/data.xml is missing — that is the company page data.');
	}
	if (existsSync(path.join(SRC, 'install.php'))) {
		// presskit() redirects every page to the installer while this exists.
		throw new Error('Delete src/install.php — presskit() redirects to it and no pages render.');
	}

	const php = await ensurePhp();
	const games = await findGames();
	const languages = await findLanguages();
	log(`Games found:     ${games.length ? games.join(', ') : '(none)'}`);
	log(`Languages found: ${languages.join(', ')} (default: ${DEFAULT_LANG})`);

	const server = spawn(php, [
		'-d', 'error_reporting=E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED',
		'-S', `127.0.0.1:${PORT}`, '-t', SRC,
	], { cwd: SRC, stdio: ['ignore', 'ignore', 'pipe'] });
	server.stderr.on('data', () => {}); // the dev server logs every request here

	try {
		const base = `http://127.0.0.1:${PORT}`;
		await waitForServer(base);

		// Empty docs/ rather than removing it — on Windows the directory itself is
		// often held open (explorer, editors, antivirus) and rmdir throws EBUSY.
		await fs.mkdir(OUT, { recursive: true });
		for (const entry of await fs.readdir(OUT)) {
			await fs.rm(path.join(OUT, entry), { recursive: true, force: true });
		}

		const pages = [];
		for (const lang of languages) {
			const q = lang === DEFAULT_LANG ? '' : `?l=${lang}`;
			pages.push([pageName('index', lang), `${base}/index.php${q}`]);
			for (const g of games) {
				const sep = lang === DEFAULT_LANG ? '' : `&l=${lang}`;
				pages.push([pageName(g, lang), `${base}/sheet.php?p=${encodeURIComponent(g)}${sep}`]);
			}
		}
		// Credits is presskit()'s own thank-you page; one copy is enough.
		pages.push(['credits.html', `${base}/sheet.php?p=credits`]);

		// The credits page ships as a shell that jQuery-loads credits.php at runtime.
		// Nothing serves PHP on Pages, so bake the fragment straight into the shell.
		const creditsBody = await fetchPage(`${base}/credits.php`);

		for (const [name, url] of pages) {
			let html = await fetchPage(url);
			if (name === 'credits.html') html = inlineCredits(html, creditsBody);
			await fs.writeFile(path.join(OUT, name), html);
			log(`  ${name.padEnd(28)} ${html.length} bytes`);
		}

		for (const f of COPY_AT_ROOT) await copyIfPresent(path.join(SRC, f), path.join(OUT, f));
		for (const d of COPY_DIRS) await copyIfPresent(path.join(SRC, d), path.join(OUT, d));
		for (const g of games) {
			for (const d of COPY_DIRS) await copyIfPresent(path.join(SRC, g, d), path.join(OUT, g, d));
		}

		// Keep GitHub Pages from running the output through Jekyll.
		await fs.writeFile(path.join(OUT, '.nojekyll'), '');

		log(`\nBuilt ${pages.length} pages into docs/`);
	} finally {
		server.kill();
	}
}

main().catch((err) => {
	console.error(`\nBuild failed: ${err.message}`);
	process.exit(1);
});
