Marketing art that is published but deliberately not shown on the press kit.

sheet.php only renders images sitting directly in <game>/images/ plus the named
folders it knows about (capsules, elements, ramon), so anything in here is
copied to the site by tools/build.mjs without appearing in any gallery or in
elements.zip.

That gives each file a stable public URL to hotlink from an HTML e-mail:
  https://vkaike2.github.io/tlgb-presskit/the_last_good_boy/images/marketing/<file>

Keep the filenames free of spaces so those URLs need no %20 escaping.
