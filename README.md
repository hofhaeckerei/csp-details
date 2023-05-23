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

ℹ️ This package needs to be enabled explicity via Admin Tools, Extension Settings!

Once enabled, details are logged to `var/log/typo3_csp-details_*.log`.

⚠️ Logged details might contain tokens or other private details of the current user sessions.
Please make sure to frequently clean up these files, and just enable the debug functionality of
this package just for a very limited time.


