<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Crée un compte admin ou réinitialise son mot de passe (demandé de façon masquée). */
#[AsCommand(name: 'app:admin', description: 'Créer un administrateur ou changer son mot de passe')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, "Email de connexion");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]) ?? new User();
        $isNew = null === $user->id;

        $password = $io->askHidden('Mot de passe (12 caractères minimum)');
        if (!$password || strlen($password) < 12) {
            $io->error('Mot de passe trop court.');

            return Command::FAILURE;
        }

        $user->email = $email;
        $user->name = $user->name ?: 'Admin';
        $user->role = 'admin';
        $user->password = $this->hasher->hashPassword($user, $password);
        $this->em->persist($user);
        $this->em->flush();

        $io->success($isNew ? 'Administrateur créé.' : 'Mot de passe mis à jour (rôle admin appliqué).');

        return Command::SUCCESS;
    }
}
