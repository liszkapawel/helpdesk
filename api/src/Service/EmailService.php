<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Invite;
use App\Entity\Ticket;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
    ) {
    }

    public function sendInvite(Invite $invite, string $toEmail): void
    {
        $html = $this->twig->render('emails/invite.html.twig', [
            'invite' => $invite,
            'org' => $invite->getOrganization(),
        ]);

        $email = (new Email())
            ->from('noreply@ticketerr.cc')
            ->to($toEmail)
            ->subject('Zaproszenie do organizacji ' . $invite->getOrganization()->getName())
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendTicketStatusChanged(Ticket $ticket): void
    {
        $recipient = $ticket->getCreatedBy()?->getEmail() ?? $ticket->getSubmitterEmail();
        if (!$recipient) {
            return;
        }

        $html = $this->twig->render('emails/ticket_status.html.twig', ['ticket' => $ticket]);

        $email = (new Email())
            ->from('noreply@ticketerr.cc')
            ->to($recipient)
            ->subject('Status zgłoszenia #' . $ticket->getId() . ' został zmieniony')
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendTicketAssigned(Ticket $ticket): void
    {
        $assignee = $ticket->getAssignedTo();
        if (!$assignee) {
            return;
        }

        $html = $this->twig->render('emails/ticket_assigned.html.twig', ['ticket' => $ticket]);

        $email = (new Email())
            ->from('noreply@ticketerr.cc')
            ->to($assignee->getEmail())
            ->subject('Przypisano Ci zgłoszenie #' . $ticket->getId())
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendNewComment(Ticket $ticket, Comment $comment): void
    {
        $recipient = $ticket->getCreatedBy()?->getEmail() ?? $ticket->getSubmitterEmail();
        if (!$recipient || $recipient === $comment->getAuthor()?->getEmail()) {
            return;
        }

        $html = $this->twig->render('emails/ticket_comment.html.twig', [
            'ticket' => $ticket,
            'comment' => $comment,
        ]);

        $email = (new Email())
            ->from('noreply@ticketerr.cc')
            ->to($recipient)
            ->subject('Nowy komentarz do zgłoszenia #' . $ticket->getId())
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendPasswordReset(string $toEmail, string $token): void
    {
        $html = $this->twig->render('emails/password_reset.html.twig', ['token' => $token]);

        $email = (new Email())
            ->from('noreply@ticketerr.cc')
            ->to($toEmail)
            ->subject('Reset hasła – Ticketerr')
            ->html($html);

        $this->mailer->send($email);
    }
}
