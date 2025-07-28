# Internationalization (i18n) - Bil24 Connector

This directory contains translation files for the Bil24 Connector WordPress plugin.

## 📁 File Structure

```
languages/
├── bil24.pot                    # Translation template (source file)
├── bil24-en_US.po/mo           # English (US) - primary language
├── bil24-ru_RU.po/mo           # Russian translation
├── bil24-de_DE.po/mo           # German translation
├── bil24-fr_FR.po/mo           # French translation
└── bil24-{locale}.po/mo        # Additional language files
```

## 🌐 Available Languages

- **English (US)** - `en_US` (primary)
- **Russian** - `ru_RU` (included)
- Additional languages can be added using the POT template

## 🔧 Text Domain

The plugin uses the text domain: **`bil24`**

All translatable strings in the plugin are wrapped with WordPress i18n functions:
- `__('Text', 'bil24')` - Get translated text
- `_e('Text', 'bil24')` - Echo translated text
- `esc_html__('Text', 'bil24')` - Get escaped translated text
- `esc_html_e('Text', 'bil24')` - Echo escaped translated text

## 🛠️ For Translators

### Creating a New Translation

1. **Copy the POT template:**
   ```bash
   cp bil24.pot bil24-{locale}.po
   ```

2. **Edit the header information in the PO file:**
   ```
   "Language: {locale}\n"
   "PO-Revision-Date: YYYY-MM-DD HH:MM+ZONE\n"
   "Last-Translator: Your Name <your.email@domain.com>\n"
   "Language-Team: Language Name <team@domain.com>\n"
   ```

3. **Translate the strings:**
   Replace empty `msgstr ""` values with your translations:
   ```
   msgid "Connection Test"
   msgstr "Test de Connexion"  # French example
   ```

4. **Generate the MO file:**
   ```bash
   msgfmt bil24-{locale}.po -o bil24-{locale}.mo
   ```

### Using Poedit (Recommended)

1. Download [Poedit](https://poedit.net/)
2. Open the POT file: `bil24.pot`
3. Select your language
4. Translate strings using Poedit's interface
5. Save to generate both PO and MO files

## 🔄 For Developers

### Adding New Translatable Strings

1. **Wrap strings in translation functions:**
   ```php
   // Instead of:
   echo "Hello World";
   
   // Use:
   esc_html_e('Hello World', 'bil24');
   ```

2. **Update the POT file:**
   ```bash
   # Using WP-CLI (recommended)
   wp i18n make-pot . languages/bil24.pot --domain=bil24
   
   # Or using xgettext
   find . -name "*.php" -exec xgettext --language=PHP --keyword=__ --keyword=_e --keyword=esc_html__ --keyword=esc_html_e --sort-output --from-code=UTF-8 --output=languages/bil24.pot {} +
   ```

3. **Update existing translations:**
   ```bash
   # Update PO files with new strings
   msgmerge --update languages/bil24-ru_RU.po languages/bil24.pot
   ```

### Translation Context

Use context for ambiguous strings:
```php
// Good - provides context
_x('Post', 'noun: a blog post', 'bil24');
_x('Post', 'verb: to publish', 'bil24');
```

### Pluralization

Handle plural forms correctly:
```php
printf(
    _n(
        'One event',
        '%s events',
        $count,
        'bil24'
    ),
    number_format_i18n($count)
);
```

## 📝 Translation Guidelines

### General Rules
- Keep translations concise and clear
- Maintain the same tone as the original
- Use native language conventions
- Test translations in the admin interface

### Technical Terms
- Keep API-related terms in English when appropriate
- Maintain consistency with WordPress core translations
- Use official translations for WordPress terms

### UI Elements
- Follow target language UI conventions
- Ensure translated text fits in the interface
- Consider text expansion/contraction

## 🧪 Testing Translations

1. **Change WordPress language:**
   - Go to Settings → General
   - Set Site Language to your locale
   - Save changes

2. **Verify translations appear:**
   - Navigate to Settings → Bil24 Connector
   - Check that interface elements are translated
   - Test connection button and messages

3. **Test pluralization:**
   - Verify singular/plural forms display correctly
   - Check numeric formatting

## 📊 Translation Status

| Language | Code | Completion | Maintainer |
|----------|------|------------|------------|
| English (US) | en_US | 100% | Core Team |
| Russian | ru_RU | 100% | Bil24 Team |
| German | de_DE | 0% | Needed |
| French | fr_FR | 0% | Needed |
| Spanish | es_ES | 0% | Needed |

## 🤝 Contributing Translations

We welcome translation contributions! 

1. **Fork the repository**
2. **Create your translation files**
3. **Test your translations**
4. **Submit a pull request**

### Translation Checklist
- [ ] Header information updated
- [ ] All strings translated
- [ ] Pluralization tested
- [ ] UI layout checked
- [ ] MO file generated
- [ ] Tested in WordPress

## 📞 Support

For translation questions or to become a language maintainer:
- GitHub Issues: [Submit Translation Issue](https://github.com/yourname/bil24-connector/issues)
- Email: translations@bil24.pro

---

**Thank you for helping make Bil24 Connector accessible worldwide! 🌍** 