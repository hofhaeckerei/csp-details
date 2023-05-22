# TYPO3 Content-Security-Policy Details

Provides additional details for CSP violations in TYPO3 v12 - like dynamic
HTML markup that was rendered, or more navigator/browser details.

There are several "surprises" with CSP violations, great details and insights
are collected at https://github.com/nico3333fr/CSP-useful/blob/master/csp-wtf/explained.md

## Installation

```bash
composer req h4ck3r31/csp-details
```

⚠️ When enabling this package (via Admin Tools, Extension Settings), the generated
logs might consume a lot of storage space withing a short amount of time. Thus, please
take care of rotating/compressing these files or just have it enabled only while debugging.

## Configuration

ℹ️ This package needs to be enabled explicity!

Extension Configurations settings (via Admin Tools, Extension Settings)
can be used to adjust the behavior of this package, as shown below:

```patch
+        'csp_details' => [
+            'enabled' => '0',
+            'persistence' => 'file',
+        ],
```

* `enabled (bool, default '0')` : Whether to capture additional CSP details at all
* `persistence ('file'|'db', default 'file')`: Where to persist the details
  + `file`: create markdown files at `var/log/csp-details/{uuid}.md`
  + `db`: store to additional database field `sys_http_report.csp_details`
