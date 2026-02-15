<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Invite;
use App\Entity\Organization;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ── Organizations ──────────────────────────────
        $techVision = $this->createOrg($manager, 'TechVision', 'techvision', 'Firma zajmująca się rozwiązaniami IT', '#3B82F6');
        $mediCare = $this->createOrg($manager, 'MediCare', 'medicare', 'Przychodnia medyczna', '#10B981');
        $eduPlatform = $this->createOrg($manager, 'EduPlatform', 'eduplatform', 'Platforma edukacyjna online', '#8B5CF6');

        // ── Categories ──────────────────────────────
        $tvInfra = $this->createCategory($manager, 'Infrastruktura', 'Serwery, sieci, VPN', $techVision);
        $tvApp = $this->createCategory($manager, 'Aplikacje', 'Problemy z oprogramowaniem', $techVision);
        $tvHardware = $this->createCategory($manager, 'Sprzęt', 'Komputery, drukarki, monitory', $techVision);
        $tvAccess = $this->createCategory($manager, 'Dostępy', 'Konta, uprawnienia, VPN', $techVision);

        $mcSystem = $this->createCategory($manager, 'System medyczny', 'Problemy z systemem HIS', $mediCare);
        $mcSprzet = $this->createCategory($manager, 'Sprzęt medyczny', 'Aparatura, drukarki recept', $mediCare);
        $mcSiec = $this->createCategory($manager, 'Sieć', 'WiFi, internet, kable', $mediCare);

        $epKursy = $this->createCategory($manager, 'Kursy', 'Problemy z kursami', $eduPlatform);
        $epPlatnosci = $this->createCategory($manager, 'Płatności', 'Faktury, zwroty', $eduPlatform);
        $epKonto = $this->createCategory($manager, 'Konto', 'Logowanie, rejestracja', $eduPlatform);

        // ── Users: TechVision ──────────────────────────────
        $tvAdmin = $this->createUser($manager, 'admin@techvision.pl', 'admin123', 'Marek', 'Kowalski', ['ROLE_ADMIN'], $techVision);
        $tvAgent1 = $this->createUser($manager, 'anna.nowak@techvision.pl', 'agent123', 'Anna', 'Nowak', ['ROLE_AGENT'], $techVision);
        $tvAgent2 = $this->createUser($manager, 'piotr.wisniewski@techvision.pl', 'agent123', 'Piotr', 'Wiśniewski', ['ROLE_AGENT'], $techVision);
        $tvUser1 = $this->createUser($manager, 'jan.zielinski@techvision.pl', 'user123', 'Jan', 'Zieliński', [], $techVision);
        $tvUser2 = $this->createUser($manager, 'katarzyna.dabrowska@techvision.pl', 'user123', 'Katarzyna', 'Dąbrowska', [], $techVision);

        // ── Users: MediCare ──────────────────────────────
        $mcAdmin = $this->createUser($manager, 'admin@medicare.pl', 'admin123', 'Tomasz', 'Lewandowski', ['ROLE_ADMIN'], $mediCare);
        $mcAgent = $this->createUser($manager, 'ewa.kaminska@medicare.pl', 'agent123', 'Ewa', 'Kamińska', ['ROLE_AGENT'], $mediCare);
        $mcUser = $this->createUser($manager, 'robert.jankowski@medicare.pl', 'user123', 'Robert', 'Jankowski', [], $mediCare);

        // ── Users: EduPlatform ──────────────────────────────
        $epAdmin = $this->createUser($manager, 'admin@eduplatform.pl', 'admin123', 'Agnieszka', 'Wójcik', ['ROLE_ADMIN'], $eduPlatform);
        $epAgent = $this->createUser($manager, 'michal.krawczyk@eduplatform.pl', 'agent123', 'Michał', 'Krawczyk', ['ROLE_AGENT'], $eduPlatform);

        $manager->flush();

        // ── Tickets: TechVision ──────────────────────────────
        $this->createTicket($manager, 'VPN nie łączy z biurem', 'Po aktualizacji systemu VPN przestał działać. Próbowałem reinstalacji klienta — bez efektu.', TicketStatus::IN_PROGRESS, TicketPriority::HIGH, $techVision, $tvUser1, $tvAgent1, $tvInfra, '-3 days');
        $this->createTicket($manager, 'Brak dostępu do SharePoint', 'Nie mogę otworzyć folderu zespołu na SharePoint. Wyświetla się błąd 403.', TicketStatus::OPEN, TicketPriority::MEDIUM, $techVision, $tvUser2, $tvAgent1, $tvAccess, '-5 days');
        $this->createTicket($manager, 'Drukarka na 2. piętrze nie drukuje', 'HP LaserJet na 2. piętrze się zacina. Wymaga serwisu.', TicketStatus::NEW, TicketPriority::LOW, $techVision, $tvUser1, null, $tvHardware, '-1 day');
        $this->createTicket($manager, 'Outlook zawiesza się przy starcie', 'Od wczoraj Outlook zamraża się na 2 minuty po otwarciu. Windows 11, Office 365.', TicketStatus::RESOLVED, TicketPriority::MEDIUM, $techVision, $tvUser2, $tvAgent2, $tvApp, '-10 days');
        $this->createTicket($manager, 'Prośba o nowy laptop', 'Potrzebuję nowego laptopa — obecny ma 5 lat i ledwo daje radę.', TicketStatus::OPEN, TicketPriority::LOW, $techVision, $tvUser1, null, $tvHardware, '-2 days');
        $this->createTicket($manager, 'Serwer testowy nie odpowiada', 'Serwer test-db-01 nie odpowiada na ping od rana. Blokuje deployment.', TicketStatus::IN_PROGRESS, TicketPriority::CRITICAL, $techVision, $tvAgent2, $tvAgent1, $tvInfra, '-1 day');
        $this->createTicket($manager, 'Nowe konto dla stażysty', 'Proszę o utworzenie konta AD i email dla nowego stażysty Jakub Malinowski, start 01.03.', TicketStatus::NEW, TicketPriority::MEDIUM, $techVision, $tvUser2, null, $tvAccess, '-6 hours');
        $this->createTicket($manager, 'Aktualizacja licencji Adobe', 'Licencje Adobe CC wygasają za tydzień. Potrzebne odnowienie dla 15 osób.', TicketStatus::OPEN, TicketPriority::HIGH, $techVision, $tvAdmin, $tvAgent2, $tvApp, '-4 days');
        $this->createTicket($manager, 'WiFi wolne w sali konferencyjnej', 'W dużej sali konferencyjnej internet jest bardzo wolny, szczególnie przy wideokonferencjach.', TicketStatus::CLOSED, TicketPriority::MEDIUM, $techVision, $tvUser1, $tvAgent1, $tvInfra, '-15 days');
        $this->createTicket($manager, 'Błąd w systemie CRM', 'Przy dodawaniu nowego kontaktu w CRM pojawia się błąd "Internal Server Error".', TicketStatus::NEW, TicketPriority::HIGH, $techVision, $tvUser2, null, $tvApp, '-12 hours');

        // ── Tickets: MediCare ──────────────────────────────
        $this->createTicket($manager, 'System HIS nie generuje recept', 'Od rana nie można wystawiać e-recept. System zwraca timeout.', TicketStatus::IN_PROGRESS, TicketPriority::CRITICAL, $mediCare, $mcUser, $mcAgent, $mcSystem, '-2 hours');
        $this->createTicket($manager, 'Drukarka recept w gabinecie 5', 'Drukarka w gabinecie 5 drukuje puste strony.', TicketStatus::OPEN, TicketPriority::HIGH, $mediCare, $mcUser, $mcAgent, $mcSprzet, '-1 day');
        $this->createTicket($manager, 'WiFi dla pacjentów nie działa', 'Sieć WiFi "MediCare-Guest" jest niedostępna od wczoraj.', TicketStatus::NEW, TicketPriority::LOW, $mediCare, $mcUser, null, $mcSiec, '-3 hours');

        // ── Tickets: EduPlatform ──────────────────────────────
        $this->createTicket($manager, 'Kurs "Python dla początkujących" nie ładuje się', 'Po opłaceniu kursu strona ładuje się w nieskończoność. Widzę spinner.', TicketStatus::OPEN, TicketPriority::HIGH, $eduPlatform, null, $epAgent, $epKursy, '-1 day', 'Marta Kowalczyk', 'marta.k@gmail.com');
        $this->createTicket($manager, 'Prośba o zwrot za kurs', 'Chciałbym zwrot za kurs "Java Advanced" — nie odpowiada mi poziom.', TicketStatus::NEW, TicketPriority::MEDIUM, $eduPlatform, null, null, $epPlatnosci, '-5 hours', 'Adam Nowicki', 'adam.nowicki@wp.pl');
        $this->createTicket($manager, 'Nie mogę zresetować hasła', 'Klikam "Zapomniałem hasła" ale mail nie przychodzi. Sprawdziłem spam.', TicketStatus::RESOLVED, TicketPriority::MEDIUM, $eduPlatform, null, $epAgent, $epKonto, '-3 days', 'Zofia Lis', 'z.lis@outlook.com');

        $manager->flush();

        // ── Comments ──────────────────────────────
        $tickets = $manager->getRepository(Ticket::class)->findAll();
        $commentData = [
            'VPN nie łączy z biurem' => [
                [$tvAgent1, 'Sprawdziłam konfigurację — certyfikat VPN wygasł. Generuję nowy.'],
                [$tvUser1, 'Dzięki, czekam na nowy certyfikat.'],
                [$tvAgent1, 'Nowy certyfikat wysłany na maila. Proszę zaimportować i spróbować ponownie.'],
            ],
            'Serwer testowy nie odpowiada' => [
                [$tvAgent1, 'Sprawdzam logi — wygląda na pełny dysk. Czyszczę stare snapshoty.'],
                [$tvAgent2, 'Czy jest ETA? Blokuje to cały zespół QA.'],
                [$tvAgent1, 'Zwolniłam 120GB. Serwer restartowany, powinien za chwilę odpowiadać.'],
            ],
            'System HIS nie generuje recept' => [
                [$mcAgent, 'Problem po stronie P1 (serwer Ministerstwa Zdrowia). Sprawdzam status.'],
                [$mcUser, 'Pacjenci czekają — czy jest obejście?'],
                [$mcAgent, 'Na razie można wystawiać recepty papierowe. P1 obiecuje naprawę do 14:00.'],
            ],
            'Outlook zawiesza się przy starcie' => [
                [$tvAgent2, 'Proszę spróbować uruchomić Outlook w trybie awaryjnym: outlook.exe /safe'],
                [$tvUser2, 'W trybie awaryjnym działa! Co dalej?'],
                [$tvAgent2, 'To pewnie wadliwy dodatek. Wyłączyłem dodatki COM — proszę uruchomić normalnie.'],
                [$tvUser2, 'Działa normalnie, dziękuję!'],
            ],
        ];

        foreach ($tickets as $ticket) {
            if (isset($commentData[$ticket->getTitle()])) {
                foreach ($commentData[$ticket->getTitle()] as $i => $cd) {
                    $comment = new Comment();
                    $comment->setContent($cd[1]);
                    $comment->setTicket($ticket);
                    $comment->setAuthor($cd[0]);
                    $manager->persist($comment);
                }
            }
        }

        // ── Invites ──────────────────────────────
        $invite = new Invite();
        $invite->setOrganization($techVision);
        $invite->setCreatedBy($tvAdmin);
        $invite->setEmail('nowy.pracownik@techvision.pl');
        $manager->persist($invite);

        $manager->flush();
    }

    private function createOrg(ObjectManager $manager, string $name, string $slug, string $description, string $color): Organization
    {
        $org = new Organization();
        $org->setName($name);
        $org->setSlug($slug);
        $org->setDescription($description);
        $org->setPrimaryColor($color);
        $manager->persist($org);
        return $org;
    }

    private function createCategory(ObjectManager $manager, string $name, string $description, Organization $org): Category
    {
        $cat = new Category();
        $cat->setName($name);
        $cat->setDescription($description);
        $cat->setOrganization($org);
        $manager->persist($cat);
        return $cat;
    }

    private function createUser(ObjectManager $manager, string $email, string $password, string $firstName, string $lastName, array $roles, Organization $org): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setOrganization($org);
        $user->setPlainPassword($password);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->eraseCredentials();
        $manager->persist($user);
        return $user;
    }

    private function createTicket(
        ObjectManager $manager,
        string $title,
        string $description,
        TicketStatus $status,
        TicketPriority $priority,
        Organization $org,
        ?User $createdBy,
        ?User $assignedTo,
        ?Category $category,
        string $createdAgo,
        ?string $submitterName = null,
        ?string $submitterEmail = null,
    ): Ticket {
        $ticket = new Ticket();
        $ticket->setTitle($title);
        $ticket->setDescription($description);
        $ticket->setStatus($status);
        $ticket->setPriority($priority);
        $ticket->setOrganization($org);
        $ticket->setCreatedBy($createdBy);
        $ticket->setAssignedTo($assignedTo);
        $ticket->setCategory($category);

        if ($submitterName) {
            $ticket->setSubmitterName($submitterName);
            $ticket->setSubmitterEmail($submitterEmail);
            $ticket->setTrackingToken(bin2hex(random_bytes(16)));
        }

        if ($status === TicketStatus::CLOSED || $status === TicketStatus::RESOLVED) {
            $ticket->setClosedAt(new \DateTimeImmutable($createdAgo . ' +2 days'));
        }

        // Use reflection to set createdAt
        $ref = new \ReflectionProperty(Ticket::class, 'createdAt');
        $ref->setValue($ticket, new \DateTimeImmutable($createdAgo));

        $manager->persist($ticket);
        return $ticket;
    }
}
