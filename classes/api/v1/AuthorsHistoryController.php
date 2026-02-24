<?php

namespace APP\plugins\generic\authorsHistory\classes\api\v1;

use APP\core\Application;
use APP\plugins\generic\authorsHistory\classes\AuthorsHistoryDAO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PKP\core\PKPBaseController;
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

    public function getGroupRoutes(): void
    {
        Route::get('', $this->getAuthorsHistory(...))
            ->name('authorsHistory.get');
    }

    public function getAuthorsHistory(IlluminateRequest $request): JsonResponse
    {
        $pkpRequest = Application::get()->getRequest();
        $context = $pkpRequest->getContext();

        if (!$context) {
            return response()->json(
                ['error' => __('plugins.generic.authorsHistory.error.submissionNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $submissionId = (int) $request->query('submissionId');

        if ($submissionId < 1) {
            return response()->json(
                ['error' => __('plugins.generic.authorsHistory.error.submissionIdRequired')],
                Response::HTTP_BAD_REQUEST
            );
        }

        $submission = \APP\facades\Repo::submission()->get($submissionId);

        if (!$submission || (int) $submission->getData('contextId') !== (int) $context->getId()) {
            return response()->json(
                ['error' => __('plugins.generic.authorsHistory.error.submissionNotFound')],
                Response::HTTP_NOT_FOUND
            );
        }

        $publication = $submission->getCurrentPublication();
        $correspondenceContact = $publication->getData('primaryContactId');
        $contextId = (int) $context->getId();
        $itemsPerPage = (int) $context->getData('itemsPerPage');
        if ($itemsPerPage < 1) {
            $itemsPerPage = 25;
        }

        $authorsHistoryDAO = new AuthorsHistoryDAO();
        $submissionType = $this->getSubmissionType();
        $listAuthorsData = [];

        foreach ($publication->getData('authors') as $author) {
            $authorData = [
                'name' => $author->getFullName(),
                'orcid' => $author->getOrcid(),
                'email' => $author->getEmail(),
                'correspondingAuthor' => ($correspondenceContact == $author->getId()),
            ];

            $givenName = $author->getLocalizedGivenName();
            $authorSubmissions = $authorsHistoryDAO->getAuthorSubmissions(
                $contextId,
                $authorData['orcid'],
                $authorData['email'],
                $givenName,
                $itemsPerPage
            );

            $authorData['submissions'] = [];
            foreach ($authorSubmissions as $authorSubmission) {
                $currentPublication = $authorSubmission->getCurrentPublication();
                $authorData['submissions'][] = [
                    'id' => $authorSubmission->getId(),
                    'title' => $currentPublication ? $currentPublication->getLocalizedFullTitle() : '',
                    'status' => $authorSubmission->getData('status'),
                    'statusLabel' => __($authorSubmission->getStatusKey()),
                    'urlWorkflow' => $pkpRequest->getDispatcher()->url(
                        $pkpRequest,
                        Application::ROUTE_PAGE,
                        null,
                        'workflow',
                        'access',
                        [$authorSubmission->getId()]
                    ),
                    'urlPublished' => ($authorSubmission->getData('status') == PKPSubmission::STATUS_PUBLISHED)
                        ? $pkpRequest->getDispatcher()->url(
                            $pkpRequest,
                            Application::ROUTE_PAGE,
                            null,
                            $submissionType,
                            'view',
                            [$authorSubmission->getBestId()]
                        )
                        : null,
                ];
            }

            $listAuthorsData[] = $authorData;
        }

        return response()->json($listAuthorsData, Response::HTTP_OK);
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
