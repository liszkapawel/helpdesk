<?php

namespace App\Security\Voter;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TicketVoter extends Voter
{
    public const VIEW = 'TICKET_VIEW';
    public const EDIT = 'TICKET_EDIT';
    public const DELETE = 'TICKET_DELETE';
    public const ASSIGN = 'TICKET_ASSIGN';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::ASSIGN])
            && $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Deny if different organization
        if ($subject->getOrganization() && $user->getOrganization()
            && $subject->getOrganization()->getId() !== $user->getOrganization()->getId()) {
            return false;
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            return true;
        }

        if (in_array('ROLE_AGENT', $roles)) {
            return in_array($attribute, [self::VIEW, self::EDIT, self::ASSIGN]);
        }

        // ROLE_USER: can view/edit own tickets, no assign
        if ($attribute === self::ASSIGN) {
            return false;
        }

        return $subject->getCreatedBy() === $user;
    }
}
