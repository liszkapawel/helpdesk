<?php

namespace App\Security\Voter;

use App\Entity\Attachment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AttachmentVoter extends Voter
{
    public const VIEW = 'ATTACHMENT_VIEW';
    public const DELETE = 'ATTACHMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::DELETE]) && $subject instanceof Attachment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Check same organization
        $ticket = $subject->getTicket() ?? $subject->getComment()?->getTicket();
        if ($ticket && $ticket->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return false;
        }

        if ($attribute === self::VIEW) {
            return true; // Same org can view
        }

        // DELETE: uploader or admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return $subject->getUploadedBy() === $user;
    }
}
