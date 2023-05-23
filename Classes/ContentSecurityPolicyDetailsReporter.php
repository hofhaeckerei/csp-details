<?php
namespace H4ck3r31\CspDetails;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Middleware\AbstractContentSecurityPolicyReporter;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDetails;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDemand;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

class ContentSecurityPolicyDetailsReporter extends AbstractContentSecurityPolicyReporter implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ExtensionConfigurationTrait;

    // @todo `AbstractContentSecurityPolicyReporter` should become a trait in the TYPO3 core
    public function __construct(
        protected readonly PolicyProvider $policyProvider,
        protected readonly ReportRepository $reportRepository,
        private readonly ExtensionConfiguration $extensionConfiguration,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $scope = $this->resolveScope($request);
        if ($scope !== null
            && $this->isCspReport($scope, $request)
            && $this->isEnabled($this->extensionConfiguration)
        ) {
            $this->persistCspReport($scope, $request);
            return new NullResponse();
        }
        return $handler->handle($request);
    }

    protected function persistCspReport(Scope $scope, ServerRequestInterface $request): void
    {
        $payload = (string)$request->getBody();
        if (!$this->isJson($payload)) {
            return;
        }
        $requestTime = (int)($request->getQueryParams()['requestTime'] ?? 0);
        $data = json_decode($payload, true);
        $originalReport = $data['csp-report'] ?? [];
        $originalReport = $this->anonymizeDetails($originalReport);
        // @todo see https://review.typo3.org/c/Packages/TYPO3.CMS/+/79136
        $reportDetails = class_exists(ReportDetails::class) ? new ReportDetails($originalReport) : $originalReport;
        $summary = $this->generateReportSummary($scope, $reportDetails);

        $demand = ReportDemand::forSummaries([$summary]);
        $demand->requestTime = $requestTime;
        $demand->scope = $scope;
        $existingReports = $this->reportRepository->findAllSummarized($demand);

        // note: it might happen, that the default report was not persisted yet (concurrent requests)
        $existingReport = $existingReports[0] ?? null;

        $enrichment = array_filter(
            $data,
            static fn (string $key) => $key === 'document' || $key === 'navigator',
            ARRAY_FILTER_USE_KEY
        );

        $this->logger->debug(
            sprintf(
                "Document:\n%s",
                    $this->indent(($enrichment['document']['html'] ?? '') . "\n")
            ),
            [
                'summary' => $summary,
                'navigator' => $enrichment['navigator'] ?? null,
                'uuid' => $existingReport?->uuid,
                'meta' => $existingReport?->meta,
                'report' => $existingReport?->details,
            ]
        );
    }

    protected function indent(string $value, string $indention = "\t"): string
    {
        $lines = preg_split('#\v#', $value);
        return implode("\n", array_map(static fn (string $line) => $indention . $line, $lines));
    }

    protected function isCspReport(Scope $scope, ServerRequestInterface $request): bool
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $contentTypeHeader = $request->getHeaderLine('content-type');

        // @todo
        // + verify current session
        // + invoke rate limiter
        // + check additional scope (snippet enrichment)

        $reportingUriBase = $this->policyProvider->getDefaultReportingUriBase($scope, $request, false);
        return $request->getMethod() === 'POST'
            && str_starts_with($normalizedParams->getRequestUri(), (string)$reportingUriBase)
            && $contentTypeHeader === 'application/csp-report+enrichment';
    }

    private function resolveScope(ServerRequestInterface $request): ?Scope
    {
        $applicationType = ApplicationType::fromRequest($request);
        if ($applicationType->isFrontend()) {
            $site = $request->getAttribute('site');
            return Scope::frontendSite($site);
        }
        if ($applicationType->isBackend()) {
            return Scope::backend();
        }
        return null;
    }
}
