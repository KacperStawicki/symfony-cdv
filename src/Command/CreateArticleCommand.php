<?php

namespace App\Command;

use App\Entity\Articles;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-article',
    description: 'Creates a new article'
)]
class CreateArticleCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Article title')
            ->addOption('content', null, InputOption::VALUE_REQUIRED, 'Article content')
            ->addOption('author-email', null, InputOption::VALUE_REQUIRED, 'Author email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = $input->getOption('title');
        $content = $input->getOption('content');
        $authorEmail = $input->getOption('author-email');

        if (!$title) {
            $title = $io->ask('Please enter article title');
        }

        if (!$content) {
            $content = $io->ask('Please enter article content');
        }

        if (!$authorEmail) {
            $authorEmail = $io->ask('Please enter author email');
        }

        try {
            $author = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $authorEmail]);

            if (!$author) {
                $io->error(sprintf('User with email %s not found', $authorEmail));
                return Command::FAILURE;
            }

            $article = new Articles();
            $article->setTitle($title);
            $article->setContent($content);
            $article->setCreated(new \DateTime());
            $article->setAuthor($author);

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $io->success(sprintf('Article "%s" was created successfully!', $title));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Could not create article: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
