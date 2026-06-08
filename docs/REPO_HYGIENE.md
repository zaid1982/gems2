# Repository hygiene

The working tree is ~600MB and tracks things that should not be in version
control. These are deliberate, reviewable operations (they change the git index
and should land in their own commit), so they are documented here rather than
done implicitly.

## Already handled

- `.gitignore` tightened: `.env*`, dev-tool caches, `response.json`, logs, OS/editor cruft.
- `composer.lock` is now intentionally tracked (CI relies on it for reproducible installs).
- `vendor/` is already untracked.

## Stop tracking scratch / generated files

`response.json`, and any one-off scratch scripts at the repo root, should be
untracked (they remain on disk):

```bash
git rm --cached response.json
# review runner.php / db_compare.php with the team; if they are scratch, move
# them under developer/ (already git-ignored) or untrack them too.
git commit -m "chore: stop tracking scratch artifacts"
```

## Vendor TCPDF -> Composer

`api/tcpdf/` (383 files) is a vendored copy of TCPDF. Replace it with the
Composer package so it is version-managed and out of the repo. This touches PDF
generation, so do it on a branch and test every PDF endpoint
(`api/pdf/*.php`, `wo.php?action=generate_pdf`, PTW/PPM PDFs).

```bash
composer require tecnickcom/tcpdf
# Point api/pdf/tcpdf_include.php (and any direct requires) at the Composer
# autoloader / vendor path instead of api/tcpdf/.
git rm -r --cached api/tcpdf
echo "api/tcpdf/" >> .gitignore   # or delete the directory once fully migrated
```

Verify each PDF still renders byte-for-byte (or visually) before merging.

## Large binaries

`License.pdf`, `Useful_Resources.pdf` and image assets inflate clone size. If
they are not needed at runtime, move them to shared storage / docs and untrack
them. For already-committed large blobs, history rewriting (git-filter-repo /
BFG) is optional and must be coordinated with the whole team.
