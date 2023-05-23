const knownSecurityPolicyViolationEventProps = [
    'documentURI',
    'referrer',
    'blockedURI',
    'violatedDirective',
    'effectiveDirective',
    'originalPolicy',
    'disposition',
    'sourceFile',
    'statusCode',
    'lineNumber',
    'columnNumber',
    'sample',
];

class CspDetailsViolationHandler {
    static isActive = false;

    constructor() {
        if (CspDetailsViolationHandler.isActive) {
            return;
        }
        CspDetailsViolationHandler.isActive = true;
        // https://chromestatus.com/feature/5164860656582656 `ReportingObserver` is probably
        // not supported when it actually would be interesting to get more details
        document.addEventListener('securitypolicyviolation', (evt) => {
            if (evt.sourceFile && this.isValidUri(evt.sourceFile)) {
                return;
            }

            const reportUriMatches = evt.originalPolicy.match(/(?!^|;\s*)report-uri\s+(?<reportUri>[^;\s$]+)/);
            if (!reportUriMatches || !reportUriMatches.groups.reportUri) {
                return;
            }

            const report = this.normalizeSecurityPolicyViolationEvent(evt);
            const details = {
                'csp-report': report,
                document: {
                    html: document.body.parentElement.outerHTML,
                },
                navigator: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    webdriver: navigator.webdriver,
                }
            };
            // defer additional requests a bit, to have regular report entity persisted
            setTimeout(() => {
                fetch(reportUriMatches.groups.reportUri, {
                    method: 'POST',
                    cache: 'no-cache',
                    headers: { 'Content-Type': 'application/csp-report+csp-details' },
                    body: JSON.stringify(details),
                });
            }, 500)
        });
    }

    normalizeSecurityPolicyViolationEvent(evt) {
        const rawEntries = [];
        for (let key in evt) {
            rawEntries.push([key, evt[key]]);
        }
        const normalizedEntries = rawEntries
            .filter(([key, value]) => knownSecurityPolicyViolationEventProps.includes(key))
            .map(([key, value]) => {
                let normalizedKey = key.replace(
                    /[A-Z]+/g,
                    (matched) => '-' + matched.toLowerCase()
                );
                if (normalizedKey === 'sample') {
                    normalizedKey = 'script-sample';
                }
                return [normalizedKey, value];
            });
        return Object.fromEntries(normalizedEntries);
    }

    isValidUri(value) {
        try {
            new URL(value);
            return true;
        } catch (error) {
            return false;
        }
    }
}
const activeCspDetailsViolationHandler = new CspDetailsViolationHandler();
export { activeCspDetailsViolationHandler, CspDetailsViolationHandler };
