<?php

namespace App\Service;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class OrganizationResolver
{
    public function __construct(
        private RequestStack $requestStack,
        private OrganizationRepository $organizationRepository,
    ) {
    }

    public function resolve(): ?Organization
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $slug = $request->headers->get('X-Org-Slug');
        if (!$slug) {
            return null;
        }

        return $this->organizationRepository->findOneBy(['slug' => $slug]);
    }
}
