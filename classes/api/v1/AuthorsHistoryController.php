<?php

namespace APP\plugins\generic\authorsHistory\classes\api\v1;

use APP\core\Application;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
use PKP\core\PKPRequest;
use PKP\security\authorization\SubmissionAccessPolicy;
use PKP\security\Role;
use PKP\submission\PKPSubmission;

class AuthorsHistoryController extends PKPBaseController
{
    public function getHandlerPath(): string
    {
        return 'authorsHistory';
    }

    public function getRouteGroupMiddleware(): array
    {
        return [
            'has.user',
            'has.context',
            self::roleAuthorizer([
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
            ]),
        ];
    }

    public function authorize(PKPRequest $request, array &$args, array $roleAssignments): bool
    {
        $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function getGroupRoutes(): void
    {
        Route::get('', $this->getAuthorsHistory(...))
            ->name('authorsHistory.get');
    }

    public function getAuthorsHistory(IlluminateRequest $request): JsonResponse
    {
        $pkpRequest = Application::get()->getRequest();
        $context = $pkpRequest->getContext();
        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

        $publication = $submission->getCurrentPublication();
        $contextId = (int) $context->getId();
        $itemsPerPage = (int) $context->getData('itemsPerPage');
        if ($itemsPerPage < 1) {
            $itemsPerPage = 25;
        }

        $listAuthorsData = $this->buildAuthorsData($publication, $contextId, $itemsPerPage, $pkpRequest);

        return response()->json($listAuthorsData, Response::HTTP_OK);
    }

    private function buildAuthorsData($publication, int $contextId, int $itemsPerPage, $pkpRequest): array
    {
        $correspondenceContact = $publication->getData('primaryContactId');
        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $listAuthorsData = [];

        foreach ($publication->getData('authors') as $author) {
            $authorData = [
                'name' => $author->getFullName(),
                'orcid' => $author->getOrcid(),
                'email' => $author->getEmail(),
                'correspondingAuthor' => ($correspondenceContact == $author->getId()),
            ];

            $authorSubmissions = $authorsHistoryDAO->getAuthorSubmissions(
                $contextId,
                $authorData['orcid'],
                $authorData['email'],
                $author->getLocalizedGivenName(),
                $itemsPerPage
            );

            $authorData['submissions'] = $this->formatSubmissions($authorSubmissions, $pkpRequest);
            $listAuthorsData[] = $authorData;
        }

        return $listAuthorsData;
    }

    private function formatSubmissions(array $authorSubmissions, $pkpRequest): array
    {
        $submissionType = $this->getSubmissionType();
        $submissions = [];

        foreach ($authorSubmissions as $authorSubmission) {
            $currentPublication = $authorSubmission->getCurrentPublication();
            $submissions[] = [
                'id' => $authorSubmission->getId(),
                'title' => $currentPublication ? $currentPublication->getLocalizedFullTitle() : '',
                'status' => $authorSubmission->getData('status'),
                'statusLabel' => __($authorSubmission->getStatusKey()),
                'urlWorkflow' => $this->buildWorkflowUrl($pkpRequest, $authorSubmission),
                'urlPublished' => $this->buildPublishedUrl($pkpRequest, $authorSubmission, $submissionType),
            ];
        }

        return $submissions;
    }

    private function buildWorkflowUrl($pkpRequest, $submission): string
    {
        return $pkpRequest->getDispatcher()->url(
            $pkpRequest,
            Application::ROUTE_PAGE,
            null,
            'workflow',
            'access',
            [$submission->getId()]
        );
    }

    private function buildPublishedUrl($pkpRequest, $submission, string $submissionType): ?string
    {
        if ($submission->getData('status') != PKPSubmission::STATUS_PUBLISHED) {
            return null;
        }

        return $pkpRequest->getDispatcher()->url(
            $pkpRequest,
            Application::ROUTE_PAGE,
            null,
            $submissionType,
            'view',
            [$submission->getBestId()]
        );
    }

    private function getSubmissionType(): string
    {
        $applicationName = substr(Application::getName(), 0, 3);

        if ($applicationName == 'ops') {
            return 'preprint';
        }

        return 'article';
    }
}
