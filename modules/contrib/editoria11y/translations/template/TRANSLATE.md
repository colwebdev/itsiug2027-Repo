# Translation workflow

The 16 `.po` language files in `translations/` are synced against a single
system-generated template: `translations/template/general.pot`.

Supported languages: `da`, `de`, `el`, `es`, `fr`, `hu`, `it`, `ja`, `nb`,
`nl`, `pl`, `pt-br`, `pt-pt`, `sv`, `uk`, `zh`.

For Spanish, use neutral international terminology. For Chinese, use
Simplified Chinese.

## Regenerate the template

The `general.pot` file is produced by the [POTX](https://www.drupal.org/project/potx)
module. Whenever code strings change, regenerate it:

```
ddev drush potx single --folder=modules/custom/editoria11y
mv general.pot web/modules/custom/editoria11y/translations/template/
```

## Merge new strings into language files

```
web/modules/custom/editoria11y/scripts/sync-translations.sh
```

This runs `msgmerge` against each `.po` file. It:

- Preserves existing translations for unchanged strings.
- Flags renamed strings as `#, fuzzy` with the previous `msgid` for review.
- Adds empty `msgstr` entries for new strings.
- Removes obsolete strings (entries that no longer appear in `general.pot`).
- Strips verbose source-location (`#:`) comments — those belong in the
  `.pot`, not in translator-facing `.po` files.

After merging:

1. Review fuzzy entries per language (`msgattrib --only-fuzzy <lang>.po`).
2. Translate untranslated entries per language (`msgattrib --untranslated <lang>.po`).
3. Validate with `msgfmt --check --statistics <lang>.po`.
4. Commit the result and bump the `template/general.pot` hash in
   `translations/manifest.json` to the new commit.

## Plural forms per language

| Languages | nplurals | Rule |
|---|---|---|
| `pl`, `uk` | 3 | `msgstr[0]` (n==1), `msgstr[1]` (2–4), `msgstr[2]` (5+) |
| `ja`, `zh`, `hu` | 1 | `msgstr[0]` only (covers all counts) |
| all others | 2 | `msgstr[0]` (n==1), `msgstr[1]` (plural) |
