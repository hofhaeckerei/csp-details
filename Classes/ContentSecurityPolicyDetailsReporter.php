<?php
namespace H4ck3r31\CspDetails;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Middleware\AbstractContentSecurityPolicyReporter;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportDemand;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentSecurityPolicyDetailsReporter extends AbstractContentSecurityPolicyReporter
{
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
        $report = $data['csp-report'] ?? [];
        $report = $this->anonymizeDetails($report);
        $summary = $this->generateReportSummary($scope, $report);

        $demand = ReportDemand::forSummaries([$summary]);
        $demand->requestTime = $requestTime;
        $demand->scope = $scope;
        $existingReports = $this->reportRepository->findAllSummarized($demand);

        $uuid = $existingReports[0]?->uuid;
        if ($uuid === null) {
            return;
        }

        $cspDetails = array_filter(
            $data,
            static fn (string $key) => $key === 'document' || $key === 'navigator',
            ARRAY_FILTER_USE_KEY
        );

        switch ($this->getPersistence($this->extensionConfiguration)) {
            case 'database':
                $this->persistToDatabase($existingReports[0], $cspDetails);
                break;
            case 'file':
            default:
                $this->persistToFileSystem($existingReports[0], $cspDetails);
        }
    }

    protected function persistToFileSystem(Report $existingReport, array $cspDetails): void
    {
        $path = Environment::getProjectPath() . '/var/log/csp-details/';
        if (!file_exists($path)) {
            GeneralUtility::mkdir_deep($path);
        }
        $filePath = $path . $existingReport->uuid . '.md';
        file_put_contents($filePath, implode("\n", [
            '## UUID',
            '`' . $existingReport->uuid . '`',
            '',
            '## Meta',
            '`' . json_encode($existingReport->meta, JSON_UNESCAPED_SLASHES) . '`',
            '',
            '## Report',
            '`' . json_encode($existingReport->details, JSON_UNESCAPED_SLASHES) . '`',
            '',
            '## Navigator',
            '`' . json_encode($cspDetails['navigator'] ?? null, JSON_UNESCAPED_SLASHES) . '`',
            '',
            '## Document',
            '```',
            ($cspDetails['document']['html'] ?? ''),
            '```',
        ]));
    }

    protected function persistToDatabase(Report $existingReport, array $cspDetails): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_http_report');
        $connection->update(
            'sys_http_report',
            ['csp_details' => json_encode($cspDetails)],
            ['uuid' => (string)$existingReport->uuid]
        );
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
            && $contentTypeHeader === 'application/csp-report+csp-details';
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
