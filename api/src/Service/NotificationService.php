<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function notify(User $user, string $type, string $message, ?string $link = null): void
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setMessage($message);
        $notification->setLink($link);

        $this->em->persist($notification);
    }

    public function notifyTicketAssigned(Ticket $ticket, User $agent, User $assignedBy): void
    {
        if ($agent->getId() === $assignedBy->getId()) {
            return;
        }

        $this->notify(
            $agent,
            'ticket_assigned',
            sprintf('Ticket #%d "%s" has been assigned to you', $ticket->getId(), $ticket->getTitle()),
            '/tickets/' . $ticket->getId(),
        );
    }

    public function notifyTicketStatusChanged(Ticket $ticket, string $oldStatus, User $changedBy): void
    {
        $owner = $ticket->getCreatedBy();
        if ($owner->getId() === $changedBy->getId()) {
            return;
        }

        $this->notify(
            $owner,
            'ticket_status',
            sprintf('Ticket #%d "%s" status changed from %s to %s', $ticket->getId(), $ticket->getTitle(), $oldStatus, $ticket->getStatus()->value),
            '/tickets/' . $ticket->getId(),
        );
    }

    public function notifyTicketCommented(Ticket $ticket, User $commenter): void
    {
        $owner = $ticket->getCreatedBy();
        if ($owner->getId() !== $commenter->getId()) {
            $this->notify(
                $owner,
                'comment',
                sprintf('%s commented on ticket #%d "%s"', $commenter->getFirstName(), $ticket->getId(), $ticket->getTitle()),
                '/tickets/' . $ticket->getId(),
            );
        }

        $assignee = $ticket->getAssignedTo();
        if ($assignee && $assignee->getId() !== $commenter->getId() && $assignee->getId() !== $owner->getId()) {
            $this->notify(
                $assignee,
                'comment',
                sprintf('%s commented on ticket #%d "%s"', $commenter->getFirstName(), $ticket->getId(), $ticket->getTitle()),
                '/tickets/' . $ticket->getId(),
            );
        }
    }
}
