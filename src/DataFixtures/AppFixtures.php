<?php

namespace App\DataFixtures;

use App\Entity\Articles;
use App\Entity\Comment;
use App\Entity\Reaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create users
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
            $manager->persist($user);
            $users[] = $user;
        }

        // Create articles
        $articles = [];
        $articleContents = [
            'Introduction to Programming' => 'Programming is the process of creating a set of instructions that tell a computer how to perform a task. Programming can be done using a variety of computer programming languages...',
            'Web Development Basics' => 'Web development is the work involved in developing a website for the Internet or an intranet. Web development can range from developing a simple single static page of plain text to complex web applications...',
            'Database Design' => 'Database design is the organization of data according to a database model. The designer determines what data must be stored and how the data elements interrelate...',
            'API Development' => 'API development involves creating application programming interfaces that define the ways different software components should interact...',
            'Software Testing' => 'Software testing is the process of evaluating and verifying that a software product or application does what it is supposed to do...'
        ];

        foreach ($articleContents as $title => $content) {
            $article = new Articles();
            $article->setTitle($title);
            $article->setContent($content);
            $article->setCreated(new \DateTime());
            $article->setAuthor($users[array_rand($users)]); // Random user as author
            $manager->persist($article);
            $articles[] = $article;
        }

        // Create comments
        $commentContents = [
            'Great article! Very informative.',
            'This helped me understand the topic better.',
            'Could you elaborate more on the second point?',
            'Looking forward to more articles like this!',
            'Very well explained, thanks for sharing.',
            'I have a question about the implementation...',
            'This is exactly what I was looking for.',
            'Interesting perspective on the topic.'
        ];

        foreach ($articles as $article) {
            // Add 2-4 random comments to each article
            $numComments = rand(2, 4);
            for ($i = 0; $i < $numComments; $i++) {
                $comment = new Comment();
                $comment->setContent($commentContents[array_rand($commentContents)]);
                $comment->setCreatedAt(new \DateTime());
                $comment->setArticle($article);
                $comment->setAuthor($users[array_rand($users)]);
                $manager->persist($comment);
            }

            // Add reactions
            foreach ($users as $user) {
                // 70% chance to add a reaction
                if (rand(1, 100) <= 70) {
                    $reaction = new Reaction();
                    $reaction->setType(rand(0, 1) === 0 ? Reaction::LIKE : Reaction::DISLIKE);
                    $reaction->setCreatedAt(new \DateTime());
                    $reaction->setArticle($article);
                    $reaction->setUser($user);
                    $manager->persist($reaction);
                }
            }
        }

        $manager->flush();
    }
}
